<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Communication extends Model
{
    use HasFactory;

    public const TYPE_MARKETING = 'marketing';

    public const TYPE_SERVICE = 'servizio';

    public const TYPES = [
        self::TYPE_MARKETING => 'Promozionale (offerte, novità)',
        self::TYPE_SERVICE => 'Di servizio (avvisi importanti)',
    ];

    protected $fillable = [
        'type',
        'subject',
        'body',
        'recipients_count',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Destinatari in base al tipo:
     *
     * - promozionale: solo i clienti registrati che hanno dato il consenso
     *   marketing (GDPR). Ognuno riceve il suo link per disiscriversi
     * - di servizio: tutti i clienti registrati + chi ha lasciato una email su
     *   un ordine. Solo per avvisi che riguardano account e ordini (es. modifica
     *   delle condizioni, problemi con le spedizioni), mai per offerte
     *
     * @return Collection<int, array{email: string, user: User|null}>
     */
    public static function recipients(string $type): Collection
    {
        $users = User::query()
            ->where('is_admin', false)
            ->whereNotNull('email')
            ->when($type === self::TYPE_MARKETING, fn ($q) => $q->where('marketing_consent', true))
            ->get()
            ->map(fn (User $user) => ['email' => mb_strtolower(trim($user->email)), 'user' => $user]);

        $guests = $type === self::TYPE_MARKETING
            ? collect()
            : Order::query()
                ->whereNotNull('customer_email')
                ->distinct()
                ->pluck('customer_email')
                ->map(fn (string $email) => ['email' => mb_strtolower(trim($email)), 'user' => null]);

        // I clienti registrati vengono prima: in caso di doppione resta la voce
        // con l'utente collegato.
        return $users->merge($guests)->unique('email')->values();
    }

    public function isMarketing(): bool
    {
        return $this->type === self::TYPE_MARKETING;
    }
}
