<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ResolvesComuneForAddress;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogin;
use App\Support\Throttle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ResolvesComuneForAddress;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/\S+\s+\S+/'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'array'],
            'address.phone' => ['required', 'string', 'max:30'],
            'address.address_line' => ['required', 'string', 'max:255'],
            'address.comune_id' => ['required', 'integer', 'exists:comuni,id'],
            'address.postal_code' => ['required', 'string', 'size:5'],
            'privacy_accepted' => ['accepted'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ], [
            'name.regex' => 'Inserisci sia il nome che il cognome.',
            'privacy_accepted.accepted' => 'Per registrarti devi dichiarare di aver compiuto 14 anni e di aver letto l\'informativa privacy.',
        ]);

        $comuneFields = $this->resolveComuneFields(
            $data['address']['comune_id'],
            $data['address']['postal_code'],
            'address.postal_code'
        );

        $user = DB::transaction(function () use ($data, $comuneFields) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'privacy_accepted_at' => now(),
            ]);

            // Facoltativo e mai preselezionato: senza consenso il cliente riceve
            // solo le comunicazioni di servizio, non le offerte.
            if ($data['marketing_consent'] ?? false) {
                $user->setMarketingConsent(true);
            }

            $user->addresses()->create([
                'label' => 'Principale',
                'recipient_name' => $data['name'],
                'phone' => $data['address']['phone'],
                'address_line' => $data['address']['address_line'],
                'comune_id' => $data['address']['comune_id'],
                'postal_code' => $comuneFields['postal_code'],
                'province' => $comuneFields['province'],
                'is_default' => true,
            ]);

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registrazione completata. Controlla la tua email per verificare l\'account prima di accedere.',
        ], 201);
    }

    public function login(Request $request)
    {
        // `login` accetta indifferentemente email o username.
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureLoginNotLocked($request, $credentials['login']);

        $user = User::findByLogin($credentials['login']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->recordFailedLogin($request, $credentials['login']);

            throw ValidationException::withMessages([
                'login' => ['Le credenziali fornite non sono corrette.'],
            ]);
        }

        $this->clearFailedLogins($request, $credentials['login']);

        // Solo dopo aver controllato la password: chi non la conosce non scopre
        // se un account esiste ed è sospeso.
        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'login' => ['Il tuo account è sospeso. Se pensi sia un errore, scrivici su WhatsApp.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'login' => ['Devi verificare la tua email prima di accedere. Controlla la tua casella di posta.'],
            ]);
        }

        UserLogin::record($user);

        return response()->json([
            'user' => $user->fresh(),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /**
     * Blocco contro chi prova a indovinare la password di un account:
     * - 5 tentativi sbagliati dallo stesso IP → account bloccato 15 minuti per quell'IP
     * - 20 tentativi sbagliati da IP qualsiasi → account bloccato 1 ora (attacchi distribuiti)
     * Contano solo i tentativi falliti; un accesso riuscito azzera il contatore per IP.
     */
    private const LOGIN_LIMITS = [
        'ip' => ['attempts' => 5, 'decay' => 15 * 60],
        'account' => ['attempts' => 20, 'decay' => 60 * 60],
    ];

    private function loginThrottleKeys(Request $request, string $login): array
    {
        $account = Str::transliterate(Str::lower(trim($login)));

        return [
            'ip' => 'login:'.$account.'|'.$request->ip(),
            'account' => 'login:'.$account,
        ];
    }

    private function ensureLoginNotLocked(Request $request, string $login): void
    {
        foreach ($this->loginThrottleKeys($request, $login) as $type => $key) {
            if (RateLimiter::tooManyAttempts($key, self::LOGIN_LIMITS[$type]['attempts'])) {
                throw ValidationException::withMessages([
                    'login' => [Throttle::message(RateLimiter::availableIn($key))],
                ])->status(429);
            }
        }
    }

    private function recordFailedLogin(Request $request, string $login): void
    {
        foreach ($this->loginThrottleKeys($request, $login) as $type => $key) {
            RateLimiter::hit($key, self::LOGIN_LIMITS[$type]['decay']);
        }
    }

    private function clearFailedLogins(Request $request, string $login): void
    {
        RateLimiter::clear($this->loginThrottleKeys($request, $login)['ip']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout effettuato.']);
    }
}
