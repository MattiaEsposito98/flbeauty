<?php

namespace App\Support;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;

/**
 * Riepilogo dell'ordine da inviare al cliente su WhatsApp dal pannello admin.
 * Il link apre la chat con il messaggio già scritto: l'admin può ancora
 * modificarlo (es. aggiungere le istruzioni di pagamento) prima di inviarlo.
 */
class OrderWhatsApp
{
    public static function url(Order $order): ?string
    {
        $phone = self::normalizePhone($order->customer_phone);

        return $phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode(self::message($order)) : null;
    }

    /**
     * Numero nel formato internazionale senza "+" richiesto da wa.me.
     * I numeri italiani scritti senza prefisso (es. 351 745 9482) ricevono il 39.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) >= 9 && strlen($digits) <= 10 && preg_match('/^[03]/', $digits)) {
            $digits = '39'.$digits;
        }

        return strlen($digits) >= 11 ? $digits : null;
    }

    public static function message(Order $order): string
    {
        $order->loadMissing(['items.product', 'shippingRate', 'discount']);

        $number = '#'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT);
        $subtotal = $order->items->sum(fn ($item) => $item->quantity * $item->unit_price);
        $shipping = (float) $order->shipping_cost;
        $discount = max(0, $subtotal + $shipping - (float) $order->total);

        $lines = [
            trim('Ciao '.self::firstName($order->customer_name)).'! Ecco il riepilogo del tuo ordine '.$number.' su F&L Beauty.',
            '',
            '*Prodotti*',
        ];

        foreach ($order->items as $item) {
            $lines[] = '- '.($item->product?->name ?? 'Prodotto').' x'.$item->quantity.': '.self::euro($item->quantity * $item->unit_price);
        }

        $lines[] = '';
        $lines[] = 'Subtotale: '.self::euro($subtotal);

        if ($order->discount && $discount > 0) {
            $lines[] = 'Sconto ('.$order->discount->code.'): -'.self::euro($discount);
        }

        $lines[] = 'Spedizione'.($order->shippingRate ? ' ('.$order->shippingRate->name.')' : '').': '.self::euro($shipping);
        $lines[] = '*Totale: '.self::euro((float) $order->total).'*';

        if (filled($order->shipping_address_line)) {
            $lines[] = '';
            $lines[] = 'Spedizione a: '.$order->shipping_address_line.', '.$order->shipping_postal_code.' '
                .$order->shipping_city.($order->shipping_province ? ' ('.$order->shipping_province.')' : '');
        }

        $lines[] = '';
        $lines[] = 'Stato: '.(OrderStatusUpdated::STATUS_LABELS[$order->status] ?? $order->status);

        if (filled($order->tracking_number)) {
            $lines[] = '';
            $lines[] = '*Tracciamento*';

            if (filled($order->carrier)) {
                $lines[] = 'Corriere: '.$order->carrier;
            }

            $lines[] = 'Numero di tracking: '.trim($order->tracking_number);

            if ($order->effective_tracking_url) {
                $lines[] = 'Segui la spedizione: '.$order->effective_tracking_url;
            }
        }

        return implode("\n", $lines);
    }

    private static function firstName(?string $name): string
    {
        return mb_convert_case(explode(' ', trim((string) $name))[0], MB_CASE_TITLE);
    }

    private static function euro(float $value): string
    {
        return number_format($value, 2, ',', '.').' €';
    }
}
