<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un cliente che si è eliminato l'account dal sito (vedi AccountController::destroy()).
 * Tiene solo il minimo: nome, email, quando si era registrato, se aveva ricevuto il codice di
 * benvenuto e quando ha eliminato l'account. Dichiarato nell'informativa privacy.
 */
class AccountDeletion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'registered_at',
        'welcome_code_issued',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'welcome_code_issued' => 'boolean',
        ];
    }

    public static function normalizeEmail(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = self::normalizeEmail($value);
    }

    /** Questa email aveva già ricevuto il codice di benvenuto con un account poi eliminato? */
    public static function hadWelcomeCode(?string $email): bool
    {
        return static::query()
            ->where('email', self::normalizeEmail($email))
            ->where('welcome_code_issued', true)
            ->exists();
    }

    /** Cancella le righe più vecchie di `shop.deletion_log_months`. Restituisce quante. */
    public static function prune(): int
    {
        return static::query()
            ->where('created_at', '<', now()->subMonths((int) config('shop.deletion_log_months')))
            ->delete();
    }
}
