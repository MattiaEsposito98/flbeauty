<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use App\Models\UserLogin;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $customers = User::query()->where('is_admin', false);

        $total = (clone $customers)->count();
        $new = (clone $customers)->where('created_at', '>=', now()->subDays(30))->count();
        $verified = (clone $customers)->whereNotNull('email_verified_at')->count();
        $marketing = (clone $customers)->where('marketing_consent', true)->count();
        $buyers = (clone $customers)->has('orders')->count();

        $logins = UserLogin::query()->where('logged_in_at', '>=', now()->subDays(30));
        $loginCount = (clone $logins)->count();
        $activeUsers = (clone $logins)->distinct()->count('user_id');

        $percent = fn (int $value) => $total > 0 ? round($value / $total * 100).'%' : '0%';

        return [
            Stat::make('Utenti registrati', $total)
                ->description('+'.$new.' negli ultimi 30 giorni')
                ->descriptionIcon(Heroicon::OutlinedUserPlus)
                ->color('primary'),

            Stat::make('Email verificate', $verified.' · '.$percent($verified))
                ->description(($total - $verified).' ancora da verificare')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->color($total - $verified > 0 ? 'warning' : 'success'),

            Stat::make('Accessi (30 giorni)', $loginCount)
                ->description($activeUsers.' '.($activeUsers === 1 ? 'utente diverso' : 'utenti diversi'))
                ->descriptionIcon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->color('info'),

            Stat::make('Hanno ordinato', $buyers.' · '.$percent($buyers))
                ->description(($total - $buyers).' registrati senza ordini')
                ->descriptionIcon(Heroicon::OutlinedShoppingBag)
                ->color('success'),

            Stat::make('Offerte via email', $marketing.' · '.$percent($marketing))
                ->description('Ricevono le comunicazioni promozionali')
                ->descriptionIcon(Heroicon::OutlinedEnvelope)
                ->color('primary'),
        ];
    }
}
