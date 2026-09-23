<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;
}
