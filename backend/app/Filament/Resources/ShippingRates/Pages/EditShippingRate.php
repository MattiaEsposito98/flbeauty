<?php

namespace App\Filament\Resources\ShippingRates\Pages;

use App\Filament\Resources\ShippingRates\ShippingRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditShippingRate extends EditRecord
{
    protected static string $resource = ShippingRateResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
