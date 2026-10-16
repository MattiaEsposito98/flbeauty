<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** All'admin: il cliente ha modificato o annullato un ordine non ancora pagato. */
class OrderChangedForAdmin extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines  cosa è cambiato
     * @param  string  $type  OrderEvent::TYPE_EDITED o TYPE_CANCELLED
     */
    public function __construct(public Order $order, public array $lines, public string $type = OrderEvent::TYPE_EDITED) {}

    public function build(): self
    {
        $number = str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);
        $cancelled = $this->type === OrderEvent::TYPE_CANCELLED;
        $subject = $cancelled
            ? "Ordine #{$number} annullato dal cliente - {$this->order->customer_name}"
            : "Ordine #{$number} modificato dal cliente - {$this->order->customer_name}";

        return $this->subject($subject)
            ->replyTo($this->order->customer_email, $this->order->customer_name)
            ->view('emails.order-changed-admin')
            ->with([
                'subject' => $subject,
                'order' => $this->order->loadMissing(['items.product', 'items.variant', 'shippingRate', 'discount']),
                'lines' => $this->lines,
                'cancelled' => $cancelled,
                'number' => $number,
                'adminUrl' => url("/admin/orders/{$this->order->id}/edit"),
            ]);
    }
}
