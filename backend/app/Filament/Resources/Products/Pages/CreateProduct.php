<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = ProductResource::class;

    // Dopo la creazione il totale del prodotto è la somma delle varianti inserite.
    protected function afterCreate(): void
    {
        $this->record->syncStockFromVariants();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }
}
