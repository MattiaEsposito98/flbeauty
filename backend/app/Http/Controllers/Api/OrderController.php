<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewOrderForAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Address;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->orders()
            ->with(['items.product', 'items.variant', 'shippingRate', 'discount'])
            ->latest()
            ->get();
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return $order->load(['items.product', 'items.variant', 'shippingRate', 'discount']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'shipping_rate_id' => ['required', 'integer', 'exists:shipping_rates,id'],
            'discount_code' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Due righe con lo stesso prodotto e la stessa variante aggirerebbero il controllo di disponibilità.
        $lines = collect($data['items'])->map(fn ($item) => $item['product_id'].'-'.($item['product_variant_id'] ?? 0));

        if ($lines->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Lo stesso prodotto (o la stessa variante) compare più volte: unisci le righe.'],
            ]);
        }

        $address = Address::findOrFail($data['address_id']);
        abort_unless($address->user_id === $request->user()->id, 403);

        $shippingRate = ShippingRate::where('id', $data['shipping_rate_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $discount = null;

        if (filled($data['discount_code'] ?? null)) {
            $discount = Discount::where('code', $data['discount_code'])
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->first();

            if (! $discount) {
                throw ValidationException::withMessages([
                    'discount_code' => ['Codice sconto non valido o scaduto.'],
                ]);
            }
        }

        $order = DB::transaction(function () use ($request, $data, $address, $shippingRate, $discount) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $variants = ProductVariant::whereIn('product_id', $products->keys())
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->groupBy('product_id');

            // Per ogni riga: quale scorta vale (variante o prodotto) e a che prezzo.
            $resolved = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Il prodotto #{$item['product_id']} non è disponibile."],
                    ]);
                }

                $productVariants = $variants->get($product->id, collect());
                $variant = null;
                $available = (int) $product->stock;
                $label = $product->name;
                $price = (float) $product->price;

                if ($productVariants->isNotEmpty()) {
                    $variant = $productVariants->firstWhere('id', $item['product_variant_id'] ?? null);

                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'items' => ["Scegli una variante valida per \"{$product->name}\"."],
                        ]);
                    }

                    $available = (int) $variant->stock;
                    $label = "{$product->name} ({$variant->name})";
                    $price = (float) ($variant->price ?? $product->price);
                }

                if ($available < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Disponibilità insufficiente per \"{$label}\" (rimasti: {$available})."],
                    ]);
                }

                $resolved[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'discount_id' => $discount?->id,
                'shipping_rate_id' => $shippingRate->id,
                'shipping_cost' => $shippingRate->price,
                'customer_name' => $address->recipient_name,
                'customer_email' => $request->user()->email,
                'customer_phone' => $address->phone,
                'shipping_address_line' => $address->address_line,
                'shipping_postal_code' => $address->postal_code,
                'shipping_city' => $address->comune?->name,
                'shipping_province' => $address->province,
                'status' => 'nuovo',
            ]);

            foreach ($resolved as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['price'],
                ]);
            }

            $order->recalculateTotal();

            return $order;
        });

        $order->load(['items.product', 'items.variant', 'shippingRate', 'discount']);

        Mail::to($order->customer_email)->send(new OrderConfirmation($order));

        if (filled(config('app.admin_order_email'))) {
            Mail::to(config('app.admin_order_email'))->send(new NewOrderForAdmin($order));
        }

        return response()->json($order, 201);
    }
}
