<?php

namespace App\Filament\Resources\EmailLogs\Pages;

use App\Filament\Resources\EmailLogs\EmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;

    public function getSubheading(): ?string
    {
        return '«Inviata» vuol dire che il server di posta ha accettato il messaggio; se poi finisce nello spam del cliente da qui non si vede. «Errore» vuol dire che non è partita: leggi il motivo nella colonna accanto. Si conservano 90 giorni.';
    }
}
