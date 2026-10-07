<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Una riga per ogni email che il sito ha provato a inviare.
 *
 * "Inviata" significa che il server di posta (Aruba) l'ha accettata: la consegna nella
 * casella del destinatario (o nello spam) non è verificabile da qui. "Errore" significa che
 * l'invio è fallito (password, server irraggiungibile, casella bloccata...).
 * Le righe si cancellano dopo 90 giorni (vedi routes/console.php).
 */
class EmailLog extends Model
{
    use Prunable;

    public const STATUS_SENT = 'inviata';

    public const STATUS_FAILED = 'errore';

    public const KINDS = [
        'QueuedVerifyEmail' => 'Verifica account',
        'VerifyEmail' => 'Verifica account',
        'QueuedResetPassword' => 'Reset password',
        'ResetPassword' => 'Reset password',
        'OrderConfirmation' => 'Conferma ordine',
        'OrderStatusUpdated' => 'Stato ordine',
        'NewOrderForAdmin' => 'Nuovo ordine (per te)',
        'BroadcastCommunication' => 'Comunicazione',
    ];

    public $timestamps = false;

    protected $fillable = ['status', 'kind', 'recipient', 'subject', 'error', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function kindLabel(?string $kind): string
    {
        return $kind ? (self::KINDS[$kind] ?? $kind) : 'Altro';
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(90));
    }
}
