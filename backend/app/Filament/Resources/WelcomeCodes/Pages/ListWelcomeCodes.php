<?php

namespace App\Filament\Resources\WelcomeCodes\Pages;

use App\Filament\Resources\WelcomeCodes\WelcomeCodeResource;
use App\Models\Discount;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWelcomeCodes extends ListRecords
{
    protected static string $resource = WelcomeCodeResource::class;

    public function getSubheading(): ?string
    {
        return "Un codice personale per ogni cliente che verifica l'email: "
            .config('shop.welcome_discount_percent').'% di sconto, valido '
            .config('shop.welcome_discount_days').' giorni, una sola volta.';
    }

    /** Schede per stato, con il numero di codici in ognuna. */
    public function getTabs(): array
    {
        $scopes = [
            'tutti' => ['Tutti', null, 'gray'],
            'da-usare' => ['Da usare', fn (Builder $q) => $q->unused()->where('ends_at', '>=', now()), 'info'],
            'in-scadenza' => ['In scadenza', fn (Builder $q) => $q->unused()->whereBetween('ends_at', [now(), now()->addDays(Discount::EXPIRING_DAYS)]), 'warning'],
            'usati' => ['Usati', fn (Builder $q) => $q->used(), 'success'],
            'scaduti' => ['Scaduti', fn (Builder $q) => $q->unused()->where('ends_at', '<', now()), 'gray'],
        ];

        return collect($scopes)->map(fn (array $tab) => Tab::make($tab[0])
            ->badge(($tab[1] ? $tab[1](Discount::welcome()) : Discount::welcome())->count())
            ->badgeColor($tab[2])
            ->modifyQueryUsing(fn (Builder $query) => $tab[1] ? $tab[1]($query) : $query))->all();
    }
}
