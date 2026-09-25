<?php

namespace App\Filament\Resources\ShippingRates\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\ShippingRates\ShippingRateResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateShippingRate extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = ShippingRateResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }
}
