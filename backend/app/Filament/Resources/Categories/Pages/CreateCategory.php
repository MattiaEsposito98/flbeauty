<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCategory extends CreateRecord
{
    use HasBackToListAction;

    protected static string $resource = CategoryResource::class;

    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function getTitle(): string
    {
        return 'Nuova categoria';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }
}
