<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\CookieBannerStats;
use App\Filament\Resources\Users\Widgets\TopCitiesChart;
use App\Filament\Resources\Users\Widgets\UserActivityChart;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use Filament\Resources\Pages\Page;

/** Scheda «Statistiche» degli utenti: numeri, grafici, città e banner cookie (la tabella sta in «Clienti»). */
class UserStats extends Page
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Statistiche utenti';

    public function getSubheading(): ?string
    {
        return 'Come vanno registrazioni, accessi, ordini e consensi.';
    }

    public function getSubNavigation(): array
    {
        return UserResource::subNavigation();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UsersOverview::class,
            UserActivityChart::class,
            TopCitiesChart::class,
            CookieBannerStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
