<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const STATUS_LABELS = [
        'nuovo' => 'In attesa di pagamento',
        'in_lavorazione' => 'In lavorazione',
        'evaso' => 'Evaso',
        'annullato' => 'Annullato',
    ];

    /**
     * Stato e tracking vengono "fotografati" qui, al momento del cambio: l'email
     * parte dalla coda più tardi e a quel punto l'ordine viene riletto dal
     * database, dove lo stato potrebbe essere già cambiato di nuovo.
     */
    public string $status;

    public ?string $carrier;

    public ?string $trackingNumber;

    public ?string $trackingUrl;

    public function __construct(public Order $order)
    {
        $this->status = $order->status;
        $this->carrier = $order->carrier;
        $this->trackingNumber = $order->tracking_number;
        $this->trackingUrl = $order->effective_tracking_url;
    }

    public function build(): self
    {
        $subject = 'Aggiornamento ordine #'.str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);

        return $this->subject($subject)
            ->view('emails.order-status')
            ->with([
                'subject' => $subject,
                'order' => $this->order,
                'status' => $this->status,
                'statusLabel' => self::STATUS_LABELS[$this->status] ?? $this->status,
                'carrier' => $this->carrier,
                'trackingNumber' => $this->trackingNumber,
                'trackingUrl' => $this->trackingUrl,
            ]);
    }
}
