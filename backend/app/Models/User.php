<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'privacy_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Solo lo staff entra nel pannello: i clienti registrati dal sito
     * vivono nella stessa tabella ma non devono potervi accedere.
     * `is_admin` non è mass assignable di proposito.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * Trova un utente a partire da email o username. Lo username non può
     * contenere "@" (vedi validazione in registrazione), quindi la presenza
     * della chiocciola basta a distinguere i due casi senza ambiguità.
     */
    public static function findByLogin(string $login): ?self
    {
        $login = trim($login);

        return str_contains($login, '@')
            ? static::where('email', $login)->first()
            : static::where('username', $login)->first();
    }

    /**
     * Registra il consenso (o la revoca) alle email promozionali con la data,
     * che serve come prova del consenso. `marketing_consent` non è mass
     * assignable di proposito: si cambia solo da qui.
     */
    /**
     * Quando un cliente verifica la sua email (link di verifica o pulsante
     * dell'admin), gli ordini fatti prima da ospite con quella email passano al
     * suo account e li vede nel profilo. Solo dopo la verifica: prima non è
     * dimostrato che l'email sia sua, e chiunque potrebbe registrarsi con
     * l'email di un altro per vederne ordini, indirizzo e telefono.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('email_verified_at') && $user->email_verified_at !== null) {
                $user->claimGuestOrders();
            }
        });
    }

    /**
     * Collega gli ordini da ospite con la stessa email (senza distinguere
     * maiuscole/minuscole). Aggiornamento diretto: non fa partire email.
     */
    public function claimGuestOrders(): int
    {
        return Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(TRIM(customer_email)) = ?', [mb_strtolower(trim($this->email))])
            ->update(['user_id' => $this->id]);
    }

    public static function findByEmail(?string $email): ?self
    {
        return filled($email)
            ? static::query()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first()
            : null;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Ordini ancora aperti: in attesa di pagamento o in lavorazione. Tengono
     * riservati i pezzi in magazzino finché non vengono evasi o annullati.
     */
    public function activeOrders(): HasMany
    {
        return $this->orders()->whereIn('status', ['nuovo', 'in_lavorazione']);
    }

    /**
     * Sospende l'account: chiude tutti gli accessi aperti e impedisce di
     * accedere di nuovo. Con `$cancelActiveOrders` annulla anche gli ordini
     * aperti, così i pezzi tornano disponibili (lo stock lo gestisce Order).
     * Restituisce il numero di ordini annullati.
     */
    public function block(?string $reason = null, bool $cancelActiveOrders = false): int
    {
        return DB::transaction(function () use ($reason, $cancelActiveOrders) {
            $cancelled = 0;

            if ($cancelActiveOrders) {
                // Uno per uno, non con update di massa: servono gli eventi del
                // modello per rimettere lo stock a posto.
                foreach ($this->activeOrders()->get() as $order) {
                    $order->update(['status' => Order::STATUS_CANCELLED]);
                    $cancelled++;
                }
            }

            $this->forceFill([
                'blocked_at' => now(),
                'blocked_reason' => filled($reason) ? trim($reason) : null,
            ])->save();

            $this->tokens()->delete();

            return $cancelled;
        });
    }

    public function unblock(): void
    {
        $this->forceFill(['blocked_at' => null, 'blocked_reason' => null])->save();
    }

    public function setMarketingConsent(bool $consent): void
    {
        if ($this->marketing_consent === $consent) {
            return;
        }

        $this->forceFill([
            'marketing_consent' => $consent,
            'marketing_consent_at' => now(),
        ])->save();
    }

    /**
     * Email di verifica e di reset password passano dalla coda: se il server
     * di posta non risponde, la richiesta del cliente non va in errore.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Indirizzo principale: da qui viene la città mostrata nell'admin.
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function logins(): HasMany
    {
        return $this->hasMany(UserLogin::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
