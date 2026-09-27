<?php

namespace App\Observers;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (blank($order->customer_email)) {
            return;
        }

        // Anche il tracking aggiunto dopo aver messo "Evaso" va comunicato.
        $trackingAdded = $order->status === 'evaso'
            && $order->wasChanged('tracking_number')
            && filled($order->tracking_number);

        if ($order->wasChanged('status') || $trackingAdded) {
            Mail::to($order->customer_email)->send(new OrderStatusUpdated($order));
        }
    }
}
