<?php

namespace App\Filament\Resources\Orders\Concerns;

use App\Models\Order;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Blocca il salvataggio se l'ordine richiederebbe più pezzi di quelli in
 * magazzino (nuove righe, quantità aumentate, ordine annullato riattivato).
 * Conta solo i pezzi in più rispetto a quelli che l'ordine tiene già riservati.
 */
trait ChecksOrderStock
{
    protected function ensureStockIsAvailable(?Order $existing): void
    {
        $needed = [];

        if (($this->data['status'] ?? 'nuovo') !== Order::STATUS_CANCELLED) {
            foreach ($this->data['items'] ?? [] as $item) {
                if (blank($item['product_id'] ?? null)) {
                    continue;
                }

                $needed[$item['product_id']] = ($needed[$item['product_id']] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
        }

        $reserved = [];

        if ($existing?->reservesStock()) {
            foreach ($existing->items()->get() as $item) {
                $reserved[$item->product_id] = ($reserved[$item->product_id] ?? 0) + $item->quantity;
            }
        }

        $problems = [];

        foreach ($needed as $productId => $quantity) {
            $extra = $quantity - ($reserved[$productId] ?? 0);
            $product = Product::find($productId);

            if ($extra > 0 && $product && $extra > $product->stock) {
                $problems[] = e("{$product->name}: servono altri {$extra} pezzi, in magazzino ne restano {$product->stock}.");
            }
        }

        if ($problems !== []) {
            Notification::make()
                ->danger()
                ->title('Disponibilità insufficiente')
                ->body(new HtmlString(implode('<br>', $problems)))
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
