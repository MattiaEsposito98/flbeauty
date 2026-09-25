<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditDiscount extends EditRecord
{
    use HasBackToListAction;

    protected static string $resource = DiscountResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
            DeleteAction::make(),
        ];
    }
}
