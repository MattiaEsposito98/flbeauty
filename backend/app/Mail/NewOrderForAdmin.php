<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderForAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build(): self
    {
        $number = str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);
        $subject = "Nuovo ordine #{$number} - {$this->order->customer_name}";

        return $this->subject($subject)
            ->replyTo($this->order->customer_email, $this->order->customer_name)
            ->view('emails.new-order-admin')
            ->with([
                'subject' => $subject,
                'order' => $this->order->loadMissing(['items.product', 'shippingRate', 'discount']),
                'adminUrl' => url("/admin/orders/{$this->order->id}/edit"),
            ]);
    }
}
