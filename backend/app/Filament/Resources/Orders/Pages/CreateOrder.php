<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\Concerns\ChecksOrderStock;
use App\Filament\Resources\Orders\Concerns\HasWhatsAppAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderConfirmation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateOrder extends CreateRecord
{
    use ChecksOrderStock;
    use HasBackToListAction;
    use HasWhatsAppAction;

    protected static string $resource = OrderResource::class;

    private bool $confirmationSent = false;

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
        ];
    }

    protected function beforeCreate(): void
    {
        $this->ensureStockIsAvailable(null);
    }

    /**
     * Gira dopo il salvataggio delle righe: l'ordine ha già prodotti e totale.
     */
    protected function afterCreate(): void
    {
        if (($this->data['send_confirmation_email'] ?? false) && filled($this->record->customer_email)) {
            Mail::to($this->record->customer_email)->send(new OrderConfirmation($this->record->fresh()));
            $this->confirmationSent = true;
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $body = $this->confirmationSent
            ? 'Email di conferma inviata a '.$this->record->customer_email.'.'
            : 'Nessuna email inviata al cliente.';

        return Notification::make()
            ->success()
            ->title('Ordine creato')
            ->body($body)
            ->actions($this->whatsAppUrl() ? [$this->whatsAppAction()] : [])
            ->persistent();
    }
}
