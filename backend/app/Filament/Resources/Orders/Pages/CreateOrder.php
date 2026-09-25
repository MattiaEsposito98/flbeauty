<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }
}
