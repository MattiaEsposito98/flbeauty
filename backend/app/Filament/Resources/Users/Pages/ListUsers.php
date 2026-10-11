<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Scheda «Clienti»: subito la tabella, con filtri rapidi in alto (le statistiche sono nell'altra scheda). */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Clienti registrati sul sito. Le statistiche sono nella scheda «Statistiche».';
    }

    public function getSubNavigation(): array
    {
        return UserResource::subNavigation();
    }

    /** Filtri rapidi con il numero di clienti in ognuno (gli account dello staff non contano). */
    public function getTabs(): array
    {
        $customers = fn () => UserResource::getEloquentQuery()->reorder();

        $tab = fn (string $label, ?\Closure $scope = null, string $color = 'gray') => Tab::make($label)
            ->badge(($scope ? $scope($customers()) : $customers())->count())
            ->badgeColor($color)
            ->modifyQueryUsing(fn (Builder $query) => $scope ? $scope($query) : $query);

        return [
            'tutti' => $tab('Tutti'),
            'da-verificare' => $tab('Da verificare', fn (Builder $q) => $q->whereNull('email_verified_at'), 'warning'),
            'hanno-ordinato' => $tab('Hanno ordinato', fn (Builder $q) => $q->has('orders'), 'success'),
            'senza-ordini' => $tab('Senza ordini', fn (Builder $q) => $q->doesntHave('orders')),
            'bloccati' => $tab('Bloccati', fn (Builder $q) => $q->whereNotNull('blocked_at'), 'danger'),
        ];
    }
}
