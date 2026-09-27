<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\Concerns\ChecksOrderStock;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use ChecksOrderStock;
    use HasBackToListAction;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }

    protected function beforeCreate(): void
    {
        $this->ensureStockIsAvailable(null);
    }
}
