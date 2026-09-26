<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\ResolvesComuneForAddress;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ResolvesComuneForAddress;

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'array'],
            'address.phone' => ['required', 'string', 'max:30'],
            'address.address_line' => ['required', 'string', 'max:255'],
            'address.comune_id' => ['required', 'integer', 'exists:comuni,id'],
            'address.postal_code' => ['required', 'string', 'size:5'],
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
            ]);

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
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Le credenziali fornite non sono corrette.'],
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Devi verificare la tua email prima di accedere. Controlla la tua casella di posta.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout effettuato.']);
    }
}
