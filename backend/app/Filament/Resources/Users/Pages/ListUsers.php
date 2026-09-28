<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\CookieBannerStats;
use App\Filament\Resources\Users\Widgets\TopCitiesChart;
use App\Filament\Resources\Users\Widgets\UserActivityChart;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Clienti registrati sul sito, con statistiche su accessi, verifiche, consensi e città.';
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
