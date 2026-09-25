<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasBackToListAction
{
    protected function backToListAction(): Action
    {
        return Action::make('back')
            ->label('Torna indietro')
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url($this->getResourceUrl('index'));
    }
}
