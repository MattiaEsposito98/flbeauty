<?php

namespace App\Observers;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && filled($order->customer_email)) {
            Mail::to($order->customer_email)->send(new OrderStatusUpdated($order));
        }
    }
}
