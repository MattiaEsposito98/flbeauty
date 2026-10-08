<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\CookieConsentStat;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CookieBannerStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Banner cookie (ultimi 30 giorni)';

    protected ?string $description = 'Conteggi anonimi (nessun dato che identifica il visitatore). «Banner mostrato» conta ogni volta che compare, quindi chi non sceglie può essere contato più volte; Analytics registra solo chi preme «Accetta».';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $stats = CookieConsentStat::query()
            ->where('date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('COALESCE(SUM(shown), 0) as shown, COALESCE(SUM(accepted), 0) as accepted, COALESCE(SUM(rejected), 0) as rejected')
            ->first();

        $shown = (int) $stats->shown;
        $accepted = (int) $stats->accepted;
        $rejected = (int) $stats->rejected;
        $answered = $accepted + $rejected;

        $share = fn (int $value) => $answered > 0 ? round($value / $answered * 100).'%' : '—';

        return [
            Stat::make('Banner mostrato', $shown)
                ->description('Volte in cui è comparso')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('gray'),

            Stat::make('Accettato', $accepted)
                ->description($share($accepted).' di chi ha scelto')
                ->descriptionIcon(Heroicon::OutlinedHandThumbUp)
                ->color('success'),

            Stat::make('Rifiutato', $rejected)
                ->description($share($rejected).' di chi ha scelto')
                ->descriptionIcon(Heroicon::OutlinedHandThumbDown)
                ->color('danger'),

            Stat::make('Nessuna scelta', max(0, $shown - $answered))
                ->description('Hanno chiuso la pagina senza scegliere')
                ->descriptionIcon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('warning'),
        ];
    }
}
