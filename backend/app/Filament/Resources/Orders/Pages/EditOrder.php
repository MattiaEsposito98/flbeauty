<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasBackToListAction;
use App\Filament\Resources\Orders\Concerns\ChecksOrderStock;
use App\Filament\Resources\Orders\Concerns\HasWhatsAppAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderUpdatedMail;
use App\Models\OrderEvent;
use App\Support\OrderChanges;
use App\Support\OrderNotices;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EditOrder extends EditRecord
{
    use ChecksOrderStock;
    use HasBackToListAction;
    use HasWhatsAppAction;

    protected static string $resource = OrderResource::class;

    /** Com'era l'ordine prima del salvataggio (per capire cosa è cambiato). */
    private array $beforeSnapshot = [];

    protected function getHeaderActions(): array
    {
        return [
            $this->backToListAction(),
            $this->notifyCustomerAction(),
            $this->whatsAppAction(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->ensureStockIsAvailable($this->record);

        $this->beforeSnapshot = OrderChanges::snapshot($this->record);
    }

    /**
     * Se l'admin ha cambiato prodotti, quantità, indirizzo, spedizione o totale lo registriamo nella
     * cronologia dell'ordine: il cliente non viene avvisato da solo, lo decide l'admin col pulsante
     * «Avvisa il cliente» (così più modifiche di fila diventano una sola email).
     */
    protected function afterSave(): void
    {
        $lines = OrderChanges::diff($this->beforeSnapshot, $after = OrderChanges::snapshot($this->record));

        if ($lines === []) {
            return;
        }

        $this->record->events()->create([
            'actor' => OrderEvent::ACTOR_ADMIN,
            'type' => OrderEvent::TYPE_EDITED,
            'summary' => $lines,
            'total_before' => $this->beforeSnapshot['total'],
            'total_after' => $after['total'],
        ]);

        Notification::make()
            ->warning()
            ->title('Ordine modificato')
            ->body('Il cliente non è ancora stato avvisato: usa «Avvisa il cliente» in alto quando hai finito.')
            ->send();
    }

    /** Modifiche dell'admin non ancora comunicate al cliente. */
    private function pendingEvents()
    {
        return $this->record->events()
            ->where('actor', OrderEvent::ACTOR_ADMIN)
            ->whereNull('notified_at')
            ->oldest('id')
            ->get();
    }

    protected function notifyCustomerAction(): Action
    {
        return Action::make('notifyCustomer')
            ->label('Avvisa il cliente')
            ->icon('heroicon-o-bell-alert')
            ->badge(fn () => $this->pendingEvents()->count() ?: null)
            ->color(fn () => $this->pendingEvents()->isNotEmpty() ? 'warning' : 'gray')
            ->disabled(fn () => $this->pendingEvents()->isEmpty() || blank($this->record->customer_email))
            ->tooltip(fn () => match (true) {
                blank($this->record->customer_email) => 'L\'ordine non ha un\'email del cliente',
                $this->pendingEvents()->isEmpty() => 'Nessuna modifica da comunicare: appare dopo aver cambiato prodotti, indirizzo, spedizione o totale',
                default => 'Manda al cliente un\'email con le modifiche e una notifica nel suo profilo',
            })
            ->requiresConfirmation()
            ->modalHeading('Avvisare il cliente?')
            ->modalDescription(fn () => new HtmlString(
                'Riceverà un\'email e una notifica nel suo profilo con queste modifiche:<br><br>• '
                .collect($this->pendingEvents())->flatMap(fn ($e) => $e->summary)->unique()->map(fn ($l) => e($l))->implode('<br>• ')
            ))
            ->modalSubmitActionLabel('Sì, avvisa')
            ->action(function () {
                $events = $this->pendingEvents();
                $lines = $events->flatMap(fn ($e) => $e->summary)->unique()->values()->all();
                $order = $this->record->load(['items.product', 'items.variant', 'shippingRate', 'discount']);

                Mail::to($order->customer_email)->send(new OrderUpdatedMail($order, $lines, byCustomer: false));
                OrderNotices::edited($order, $lines, byCustomer: false);

                $order->events()->whereKey($events->pluck('id'))->update(['notified_at' => now()]);

                Notification::make()->success()->title('Cliente avvisato')->body('Email e notifica inviate.')->send();
            });
    }
}
