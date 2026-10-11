<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /** Sotto questa soglia (pezzi) il prodotto compare in "Scorte basse". */
    public const LOW_STOCK = 5;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Schede rapide in alto, con il numero di prodotti in ognuna. */
    public function getTabs(): array
    {
        $tab = fn (string $label, ?\Closure $scope = null, ?string $color = null) => Tab::make($label)
            ->badge(($scope ? $scope(Product::query()) : Product::query())->count())
            ->badgeColor($color ?? 'gray')
            ->modifyQueryUsing(fn (Builder $query) => $scope ? $scope($query) : $query);

        return [
            'tutti' => $tab('Tutti'),
            'esauriti' => $tab('Esauriti', fn (Builder $q) => $q->where('stock', 0), 'danger'),
            'scorte-basse' => $tab('Scorte basse', fn (Builder $q) => $q->whereBetween('stock', [1, self::LOW_STOCK]), 'warning'),
            'nascosti' => $tab('Nascosti', fn (Builder $q) => $q->where('is_active', false)),
        ];
    }
}
