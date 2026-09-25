<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateDiscount extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = DiscountResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }
}
