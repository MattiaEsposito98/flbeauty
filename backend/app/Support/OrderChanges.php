<?php

namespace App\Support;

use App\Models\Order;

/**
 * «Fotografia» di un ordine prima e dopo una modifica, e le righe di testo che dicono cosa è cambiato
 * (usate nelle email, nelle notifiche e nella cronologia dell'ordine).
 */
class OrderChanges
{
    /** @return array{items: array<string, array{name: string, qty: int}>, total: float, shipping_name: ?string, shipping_cost: float, address: string} */
    public static function snapshot(Order $order): array
    {
        $items = [];

        foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
            $key = $item->product_id.'-'.($item->product_variant_id ?? 0);
            $items[$key] = [
                'name' => $item->displayName(),
                'qty' => ($items[$key]['qty'] ?? 0) + $item->quantity,
            ];
        }

        $order->refresh();

        return [
            'items' => $items,
            'total' => (float) $order->total,
            'shipping_name' => $order->shippingRate?->name,
            'shipping_cost' => (float) $order->shipping_cost,
            'address' => trim(implode(', ', array_filter([
                $order->shipping_address_line,
                trim($order->shipping_postal_code.' '.$order->shipping_city.($order->shipping_province ? " ({$order->shipping_province})" : '')),
            ]))),
        ];
    }

    /**
     * Cosa è cambiato tra le due fotografie. Vuoto se non è cambiato niente di importante.
     *
     * @return list<string>
     */
    public static function diff(array $before, array $after): array
    {
        $lines = [];

        foreach ($after['items'] as $key => $item) {
            if (! isset($before['items'][$key])) {
                $lines[] = "Aggiunto: {$item['name']} × {$item['qty']}";
            } elseif ($before['items'][$key]['qty'] !== $item['qty']) {
                $lines[] = "{$item['name']}: quantità da {$before['items'][$key]['qty']} a {$item['qty']}";
            }
        }

        foreach ($before['items'] as $key => $item) {
            if (! isset($after['items'][$key])) {
                $lines[] = "Tolto: {$item['name']}";
            }
        }

        if ($before['address'] !== $after['address']) {
            $lines[] = 'Indirizzo di spedizione: '.$after['address'];
        }

        if ($before['shipping_name'] !== $after['shipping_name'] || $before['shipping_cost'] !== $after['shipping_cost']) {
            $lines[] = 'Spedizione: '.self::shipping($before).' → '.self::shipping($after);
        }

        if ($lines !== [] || $before['total'] !== $after['total']) {
            if ($before['total'] !== $after['total']) {
                $lines[] = 'Totale: '.OrderNotices::money($before['total']).' → '.OrderNotices::money($after['total']);
            }
        }

        return $lines;
    }

    private static function shipping(array $snapshot): string
    {
        return trim(($snapshot['shipping_name'] ?? 'Spedizione').' '.OrderNotices::money($snapshot['shipping_cost']));
    }
}
