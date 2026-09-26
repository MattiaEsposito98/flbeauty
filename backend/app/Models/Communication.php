<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
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
     * Indirizzi email destinatari: clienti registrati + clienti guest che hanno
     * lasciato una email su un ordine, senza duplicati.
     *
     * @return Collection<int, string>
     */
    public static function recipientEmails(): Collection
    {
        $registered = User::query()
            ->where('is_admin', false)
            ->whereNotNull('email')
            ->pluck('email');

        $guests = Order::query()
            ->whereNotNull('customer_email')
            ->distinct()
            ->pluck('customer_email');

        return $registered->merge($guests)
            ->map(fn (string $email) => mb_strtolower(trim($email)))
            ->unique()
            ->values();
    }
}
