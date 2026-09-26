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

    public function __construct(public Order $order) {}

    public function build(): self
    {
        $subject = 'Conferma ordine #'.str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);

        return $this->subject($subject)
            ->view('emails.order-confirmation')
            ->with([
                'subject' => $subject,
                'order' => $this->order,
            ]);
    }
}
