<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Events\JobFailed;
use Throwable;

/**
 * Tiene traccia di ogni email: quelle accettate dal server di posta e quelle fallite.
 * Non deve mai far fallire un invio: ogni errore qui viene solo segnalato nel registro.
 */
class LogMailEvents
{
    public function onSent(MessageSent $event): void
    {
        try {
            $message = $event->message;

            EmailLog::create([
                'status' => EmailLog::STATUS_SENT,
                'kind' => self::kindFromData($event->data),
                'recipient' => self::addresses($message->getTo()),
                'subject' => $message->getSubject(),
                'created_at' => now(),
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

            EmailLog::create([
                'status' => EmailLog::STATUS_FAILED,
                'kind' => $kind,
                'recipient' => $recipient ?: null,
                'subject' => $subject,
                'error' => mb_substr($event->exception->getMessage(), 0, 1000),
                'created_at' => now(),
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
