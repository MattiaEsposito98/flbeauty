<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Al cliente: il suo ordine è stato modificato, con l'elenco di cosa è cambiato e il nuovo riepilogo.
 * `byCustomer`: l'ha modificato lui (è la conferma); altrimenti l'ha aggiornato l'admin («Avvisa il cliente»).
 */
class OrderUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  list<string>  $lines */
    public function __construct(public Order $order, public array $lines, public bool $byCustomer = false) {}

    public function build(): self
    {
        $number = str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);
        $subject = $this->byCustomer ? "Hai modificato l'ordine #{$number}" : "Il tuo ordine #{$number} è stato aggiornato";

        return $this->subject($subject)
            ->view('emails.order-updated')
            ->with([
                'subject' => $subject,
                'order' => $this->order->loadMissing(['items.product', 'items.variant', 'shippingRate', 'discount']),
                'lines' => $this->lines,
                'byCustomer' => $this->byCustomer,
                'number' => $number,
            ]);
    }
}
