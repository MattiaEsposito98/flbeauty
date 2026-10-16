<?php

namespace App\Filament\Resources\Discounts\Pages;

use App\Filament\Resources\Discounts\DiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscounts extends ListRecords
{
    protected static string $resource = DiscountResource::class;

    public function getSubNavigation(): array
    {
        return DiscountResource::subNavigation();
    }

    public function getSubheading(): ?string
    {
        return 'Codici validi per tutti. I codici di benvenuto (uno per cliente) sono nella scheda «Codici di benvenuto».';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
