<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tiene traccia di ogni email nel registro dedicato `storage/logs/mail-AAAA-MM-GG.log`
 * (canale `mail`, 14 giorni): quelle accettate dal server di posta e quelle fallite.
 * Non deve mai far fallire un invio: ogni errore qui viene solo segnalato.
 *
 * "INVIATA" vuol dire che il server di posta (Aruba) ha accettato il messaggio: se poi
 * finisce nello spam del destinatario da qui non si vede. "ERRORE" vuol dire che non è partita.
 */
class LogMailEvents
{
    public function onSent(MessageSent $event): void
    {
        try {
            $message = $event->message;

            Log::channel('mail')->info('INVIATA', [
                'tipo' => self::kindFromData($event->data),
                'a' => self::addresses($message->getTo()),
                'oggetto' => $message->getSubject(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function onFailed(JobFailed $event): void
    {
        try {
            $payload = $event->job->payload();
            $name = $payload['data']['commandName'] ?? '';

            if (! in_array($name, [SendQueuedMailable::class, SendQueuedNotifications::class], true)) {
                return;
            }

            $kind = null;
            $recipient = null;
            $subject = null;

            $command = @unserialize($payload['data']['command'] ?? '');

            if ($command instanceof SendQueuedMailable) {
                $kind = class_basename($command->mailable);
                $recipient = collect($command->mailable->to)->pluck('address')->filter()->implode(', ');
                $subject = $command->mailable->subject;
            } elseif ($command instanceof SendQueuedNotifications) {
                $kind = class_basename($command->notification);
                $recipient = collect($command->notifiables)
                    ->map(fn ($n) => method_exists($n, 'routeNotificationFor') ? $n->routeNotificationFor('mail') : null)
                    ->flatten()->filter()->implode(', ');
            }

            Log::channel('mail')->error('ERRORE', [
                'tipo' => $kind,
                'a' => $recipient ?: null,
                'oggetto' => $subject,
                'motivo' => mb_substr($event->exception->getMessage(), 0, 1000),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function kindFromData(array $data): ?string
    {
        $class = $data['__laravel_mailable'] ?? $data['__laravel_notification'] ?? null;

        return $class ? class_basename($class) : null;
    }

    /** @param  array<int, \Symfony\Component\Mime\Address>  $addresses */
    private static function addresses(array $addresses): ?string
    {
        $list = collect($addresses)->map(fn ($a) => $a->getAddress())->implode(', ');

        return $list !== '' ? $list : null;
    }
}
