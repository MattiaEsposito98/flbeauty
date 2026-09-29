<?php

namespace App\Filament\Resources\Orders\Concerns;

use App\Support\OrderWhatsApp;
use Filament\Actions\Action;

/**
 * "Invia su WhatsApp": apre WhatsApp con il riepilogo dell'ordine già scritto,
 * nella chat del numero del cliente. Link e stato vengono calcolati al momento,
 * così dopo aver salvato un telefono nuovo il pulsante è subito aggiornato.
 * Senza un numero valido il pulsante resta visibile ma disattivato, con il motivo.
 */
trait HasWhatsAppAction
{
    protected function whatsAppAction(): Action
    {
        return Action::make('whatsapp')
            ->label('Invia su WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color(fn () => $this->whatsAppUrl() ? 'success' : 'gray')
            ->url(fn () => $this->whatsAppUrl())
            ->openUrlInNewTab()
            ->disabled(fn () => ! $this->whatsAppUrl())
            ->tooltip(fn () => match (true) {
                (bool) $this->whatsAppUrl() => 'Apre WhatsApp con il riepilogo già scritto, con i dati salvati: se hai appena modificato l\'ordine (es. il tracking), salva prima',
                filled($this->record->customer_phone) => 'Il numero di telefono non sembra valido',
                default => 'Aggiungi il telefono del cliente per inviare il riepilogo',
            });
    }

    protected function whatsAppUrl(): ?string
    {
        return $this->record ? OrderWhatsApp::url($this->record) : null;
    }
}
