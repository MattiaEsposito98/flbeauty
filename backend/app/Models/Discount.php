<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    public const KIND_STANDARD = 'standard';

    /** Codice personale mandato nell'email di benvenuto: un solo account, una sola volta. */
    public const KIND_WELCOME = 'welcome';

    /** «In scadenza» per l'admin: mancano al massimo questi giorni. */
    public const EXPIRING_DAYS = 7;

    protected $fillable = [
        'code',
        'kind',
        'user_id',
        'description',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'emailed_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'emailed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isWelcome(): bool
    {
        return $this->kind === self::KIND_WELCOME;
    }

    /** Gli ordini annullati non contano: se l'ordine salta, il codice torna disponibile (finché non scade). */
    public function scopeUsed(Builder $query): Builder
    {
        return $query->whereHas('orders', fn (Builder $q) => $q->where('status', '!=', Order::STATUS_CANCELLED));
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->whereDoesntHave('orders', fn (Builder $q) => $q->where('status', '!=', Order::STATUS_CANCELLED));
    }

    public function scopeWelcome(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_WELCOME);
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_STANDARD);
    }

    public function isUsed(): bool
    {
        return $this->orders()->where('status', '!=', Order::STATUS_CANCELLED)->exists();
    }

    /** Il codice come lo scrive il cliente: senza spazi e senza distinguere maiuscole/minuscole. */
    public static function findByCode(?string $code): ?self
    {
        $code = mb_strtolower(trim((string) $code));

        return $code === '' ? null : static::whereRaw('LOWER(code) = ?', [$code])->first();
    }

    /**
     * Perché questo codice NON si può usare adesso, per questo cliente (null = si può usare).
     * Un codice personale di un altro cliente risulta «non valido», senza rivelare che esiste.
     */
    public function errorFor(?User $user): ?string
    {
        $invalid = 'Codice sconto non valido o scaduto.';

        if (! $this->is_active) {
            return $invalid;
        }

        if ($this->isWelcome() && (! $user || $this->user_id !== $user->id)) {
            return $invalid;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return $invalid;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return $this->isWelcome() ? 'Il tuo codice di benvenuto è scaduto.' : $invalid;
        }

        if ($this->isWelcome() && $this->isUsed()) {
            return 'Hai già usato il tuo codice di benvenuto.';
        }

        return null;
    }
}
