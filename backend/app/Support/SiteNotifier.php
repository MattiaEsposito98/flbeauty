<?php

namespace App\Support;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifiche dentro il sito (la campanella): ogni email ai clienti registrati ha anche la sua
 * notifica, così chi perde l'email (o la trova nello spam) la rivede dal sito. Stanno nella
 * tabella `notifications` di Laravel e si cancellano da sole dopo `shop.notification_days`.
 *
 * Una notifica non deve mai bloccare l'operazione che l'ha generata (ordine, verifica email…):
 * gli errori vengono solo segnalati nei log.
 */
class SiteNotifier
{
    public const TYPE = 'site';

    /**
     * @param  string  $kind  tipo (order, welcome, promo, service…): decide l'icona nel sito
     * @param  string|null  $url  indirizzo nel sito, es. «/ordini/12»
     */
    public static function toUser(?User $user, string $kind, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! $user || $user->is_admin) {
            return;
        }

        self::toUsers([$user], $kind, $title, $body, $url);
    }

    /** @param  iterable<User>  $users */
    public static function toUsers(iterable $users, string $kind, string $title, ?string $body = null, ?string $url = null): void
    {
        try {
            $data = json_encode(['kind' => $kind, 'title' => $title, 'body' => $body, 'url' => $url], JSON_UNESCAPED_UNICODE);
            $now = now();
            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    'id' => (string) Str::orderedUuid(),   // ordinabile nel tempo: a parità di secondo restano in ordine
                    'type' => self::TYPE,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => $data,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('notifications')->insert($chunk);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Campanella del pannello admin (nuovo ordine, ordine modificato o annullato dal cliente). */
    public static function toAdmins(string $title, ?string $body = null, ?string $adminUrl = null, string $color = 'info'): void
    {
        try {
            $admins = User::where('is_admin', true)->get();

            if ($admins->isEmpty()) {
                return;
            }

            $notification = FilamentNotification::make()
                ->title($title)
                ->body($body)
                ->color($color);

            if ($adminUrl) {
                $notification->actions([Action::make('open')->label('Apri')->url($adminUrl)->markAsRead()]);
            }

            $notification->sendToDatabase($admins);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Cancella le notifiche vecchie (campanella clienti e admin). Restituisce quante. */
    public static function prune(?int $days = null): int
    {
        return DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($days ?? (int) config('shop.notification_days')))
            ->delete();
    }
}
