<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use HasBackToListAction;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
            DeleteAction::make(),
        ];
    }
}
