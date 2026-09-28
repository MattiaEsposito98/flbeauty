<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
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
