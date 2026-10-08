<?php

namespace App\Filament\Resources\Orders\Concerns;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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
        // Le scorte si contano per riga di magazzino: la variante se c'è, altrimenti il prodotto.
        $needed = [];

        if (($this->data['status'] ?? 'nuovo') !== Order::STATUS_CANCELLED) {
            foreach ($this->data['items'] ?? [] as $item) {
                if (blank($item['product_id'] ?? null)) {
                    continue;
                }

                $key = $item['product_id'].':'.(($item['product_variant_id'] ?? null) ?: 0);
                $needed[$key] = ($needed[$key] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
        }

        $reserved = [];

        if ($existing?->reservesStock()) {
            foreach ($existing->items()->get() as $item) {
                $key = $item->product_id.':'.($item->product_variant_id ?: 0);
                $reserved[$key] = ($reserved[$key] ?? 0) + $item->quantity;
            }
        }

        $problems = [];

        foreach ($needed as $key => $quantity) {
            [$productId, $variantId] = array_map('intval', explode(':', $key));
            $extra = $quantity - ($reserved[$key] ?? 0);
            $product = Product::find($productId);
            $variant = $variantId ? ProductVariant::find($variantId) : null;

            if ($extra <= 0 || ! $product) {
                continue;
            }

            $stock = $variant ? $variant->stock : $product->stock;
            $name = $variant ? "{$product->name} ({$variant->name})" : $product->name;

            if ($extra > $stock) {
                $problems[] = e("{$name}: servono altri {$extra} pezzi, in magazzino ne restano {$stock}.");
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
