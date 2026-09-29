<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Stato al momento della creazione (vedi OrderStatusUpdated). */
    public string $status;

    public function __construct(public Order $order)
    {
        $this->status = $order->status;
    }

    public function build(): self
    {
        $subject = 'Conferma ordine #'.str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);

        return $this->subject($subject)
            ->view('emails.order-confirmation')
            ->with([
                'subject' => $subject,
                'order' => $this->order,
                'status' => $this->status,
                'statusLabel' => OrderStatusUpdated::STATUS_LABELS[$this->status] ?? $this->status,
            ]);
    }
}
