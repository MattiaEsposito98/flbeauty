<?php

namespace App\Support;

use App\Mail\OrderChangedForAdmin;
use App\Mail\OrderUpdatedMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Il cliente modifica o annulla il proprio ordine finché è «In attesa di pagamento» (stato `nuovo`).
 * Con il pagamento ricevuto (l'admin lo mette «In lavorazione») l'ordine si blocca.
 *
 * Tutto avviene in una transazione con l'ordine bloccato: se l'admin cambia lo stato nello stesso
 * momento, uno dei due arriva dopo e trova lo stato nuovo. Scorte e totali li aggiornano da soli gli
 * eventi di OrderItem/Order.
 */
class OrderEditor
{
    public const LOCKED_MESSAGE = 'Questo ordine non si può più modificare dal sito (è già in lavorazione, spedito o annullato). Per cambiamenti scrivici su WhatsApp.';

    /**
     * @param  array{items: list<array{product_id: int, product_variant_id?: ?int, quantity: int}>, address_id: int, shipping_rate_id: int}  $data
     * @return array{order: Order, lines: list<string>}
     */
    public static function update(int $orderId, User $user, array $data): array
    {
        $lines = [];

        $order = DB::transaction(function () use ($orderId, $user, $data, &$lines) {
            $order = self::lockedOrder($orderId, $user);

            $address = Address::findOrFail($data['address_id']);
            abort_unless($address->user_id === $user->id, 403);

            $rate = ShippingRate::where('id', $data['shipping_rate_id'])->where('is_active', true)->firstOrFail();

            $desired = collect($data['items']);

            if ($desired->map(fn ($i) => $i['product_id'].'-'.($i['product_variant_id'] ?? 0))->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => ['Lo stesso prodotto (o la stessa variante) compare più volte: unisci le righe.']]);
            }

            $before = OrderChanges::snapshot($order);

            $products = Product::whereIn('id', $desired->pluck('product_id'))->where('is_active', true)->lockForUpdate()->get()->keyBy('id');
            $variants = ProductVariant::whereIn('product_id', $products->keys())->where('is_active', true)->lockForUpdate()->get()->groupBy('product_id');

            // Righe già nell'ordine, per prodotto+variante: i loro pezzi sono già riservati e il prezzo resta quello di allora.
            $existing = $order->items()->get()->groupBy(fn ($i) => $i->product_id.'-'.($i->product_variant_id ?? 0));

            $resolved = [];

            foreach ($desired as $line) {
                $product = $products->get($line['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages(['items' => ['Un prodotto non è più disponibile: toglilo dall\'ordine per continuare.']]);
                }

                $productVariants = $variants->get($product->id, collect());
                $variant = null;
                $stock = (int) $product->stock;
                $label = $product->name;
                $price = (float) $product->price;

                if ($productVariants->isNotEmpty()) {
                    $variant = $productVariants->firstWhere('id', $line['product_variant_id'] ?? null);

                    if (! $variant) {
                        throw ValidationException::withMessages(['items' => ["Scegli una variante valida per \"{$product->name}\"."]]);
                    }

                    $stock = (int) $variant->stock;
                    $label = "{$product->name} ({$variant->name})";
                    $price = (float) ($variant->price ?? $product->price);
                } elseif (! empty($line['product_variant_id'])) {
                    throw ValidationException::withMessages(['items' => ["Variante non valida per \"{$product->name}\"."]]);
                }

                $key = $product->id.'-'.($variant?->id ?? 0);
                $rows = $existing->get($key, collect());
                $alreadyReserved = (int) $rows->sum('quantity');

                if ($line['quantity'] > $stock + $alreadyReserved) {
                    throw ValidationException::withMessages(['items' => ["Disponibilità insufficiente per \"{$label}\" (puoi averne al massimo ".($stock + $alreadyReserved).').']]);
                }

                $resolved[$key] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => $rows->isNotEmpty() ? $rows->first()->unit_price : $price,
                ];
            }

            // Applica: aggiorna le righe che restano, crea le nuove, elimina quelle tolte.
            foreach ($existing as $key => $rows) {
                if (! isset($resolved[$key])) {
                    $rows->each->delete();

                    continue;
                }

                $rows->first()->update(['quantity' => $resolved[$key]['quantity']]);
                $rows->slice(1)->each->delete();
            }

            foreach ($resolved as $key => $line) {
                if (! $existing->has($key)) {
                    $order->items()->create($line);
                }
            }

            $order->update([
                'shipping_rate_id' => $rate->id,
                'shipping_cost' => $rate->price,
                'customer_name' => $address->recipient_name,
                'customer_phone' => $address->phone,
                'shipping_address_line' => $address->address_line,
                'shipping_postal_code' => $address->postal_code,
                'shipping_city' => $address->comune?->name,
                'shipping_province' => $address->province,
            ]);

            $order->recalculateTotal();

            $lines = OrderChanges::diff($before, $after = OrderChanges::snapshot($order));

            if ($lines !== []) {
                $order->events()->create([
                    'actor' => OrderEvent::ACTOR_CUSTOMER,
                    'type' => OrderEvent::TYPE_EDITED,
                    'summary' => $lines,
                    'total_before' => $before['total'],
                    'total_after' => $after['total'],
                    'notified_at' => now(),
                ]);
            }

            return $order;
        });

        $order->load(['items.product', 'items.variant', 'shippingRate', 'discount']);

        if ($lines !== []) {
            Mail::to($order->customer_email)->send(new OrderUpdatedMail($order, $lines, byCustomer: true));

            if (filled(config('app.admin_order_email'))) {
                Mail::to(config('app.admin_order_email'))->send(new OrderChangedForAdmin($order, $lines, OrderEvent::TYPE_EDITED));
            }

            OrderNotices::edited($order, $lines, byCustomer: true);
        }

        return ['order' => $order, 'lines' => $lines];
    }

    /** Annulla l'ordine: i pezzi tornano disponibili e l'eventuale codice di benvenuto torna utilizzabile. */
    public static function cancel(int $orderId, User $user): Order
    {
        $order = DB::transaction(function () use ($orderId, $user) {
            $order = self::lockedOrder($orderId, $user);

            $order->events()->create([
                'actor' => OrderEvent::ACTOR_CUSTOMER,
                'type' => OrderEvent::TYPE_CANCELLED,
                'summary' => ['Ordine annullato dal cliente'],
                'total_before' => $order->total,
                'total_after' => $order->total,
                'notified_at' => now(),
            ]);

            // Il cambio di stato fa partire da solo l'email «Annullato» al cliente, la sua notifica
            // e rimette a posto le scorte (OrderObserver / Order::booted).
            $order->update(['status' => Order::STATUS_CANCELLED]);

            return $order;
        });

        $order->load(['items.product', 'items.variant', 'shippingRate', 'discount']);

        if (filled(config('app.admin_order_email'))) {
            Mail::to(config('app.admin_order_email'))->send(new OrderChangedForAdmin($order, ['Il cliente ha annullato l\'ordine.'], OrderEvent::TYPE_CANCELLED));
        }

        OrderNotices::cancelledByCustomer($order);

        return $order;
    }

    private static function lockedOrder(int $orderId, User $user): Order
    {
        $order = Order::lockForUpdate()->findOrFail($orderId);

        abort_unless($order->user_id === $user->id, 403);

        if ($order->status !== 'nuovo') {
            throw ValidationException::withMessages(['order' => [self::LOCKED_MESSAGE]]);
        }

        return $order;
    }
}
