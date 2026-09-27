<?php

namespace App\Filament\Resources\ShippingRates\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\ShippingRates\ShippingRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditShippingRate extends EditRecord
{
    use HasBackToListAction;

    protected static string $resource = ShippingRateResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function getTitle(): string
    {
        return 'Modifica metodo di spedizione';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
            DeleteAction::make(),
        ];
    }
}
