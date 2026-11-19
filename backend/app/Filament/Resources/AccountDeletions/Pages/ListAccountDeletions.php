<?php

namespace App\Filament\Resources\AccountDeletions\Pages;

use App\Filament\Resources\AccountDeletions\AccountDeletionResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListAccountDeletions extends ListRecords
{
    protected static string $resource = AccountDeletionResource::class;

    public function getSubNavigation(): array
    {
        return UserResource::subNavigation();
    }

    public function getSubheading(): ?string
    {
        return 'Clienti che si sono eliminati l\'account dal sito. Restano nel registro '
            .config('shop.deletion_log_months').' mesi, poi spariscono da soli. Chi si riregistra con la stessa email non riceve un secondo codice di benvenuto.';
    }
}
