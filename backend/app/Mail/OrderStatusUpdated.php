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

    public function __construct(public Order $order) {}

    public function build(): self
    {
        $subject = 'Aggiornamento ordine #'.str_pad((string) $this->order->id, 5, '0', STR_PAD_LEFT);

        return $this->subject($subject)
            ->view('emails.order-status')
            ->with([
                'subject' => $subject,
                'order' => $this->order,
                'statusLabel' => self::STATUS_LABELS[$this->order->status] ?? $this->order->status,
            ]);
    }
}
