<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return $this->respondWithItems($request);
    }

    /**
     * Aggiunge una quantità al carrello: se lo stesso prodotto (e la stessa variante) è già
     * presente, la quantità si somma a quella esistente (non la sostituisce).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::with('activeVariants')->findOrFail($data['product_id']);
        [$variant, $available] = $this->resolveStock($product, $data['product_variant_id'] ?? null);

        $item = $request->user()->cartItems()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
        $item->quantity = min(($item->exists ? $item->quantity : 0) + $data['quantity'], $available);

        if ($item->quantity > 0) {
            $item->save();
        }

        return $this->respondWithItems($request);
    }

    /**
     * Imposta la quantità esatta per un prodotto (e la sua variante). Una quantità <= 0
     * rimuove la riga.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer'],
            'product_variant_id' => ['nullable', 'integer'],
        ]);

        $product->load('activeVariants');
        [$variant, $available] = $this->resolveStock($product, $data['product_variant_id'] ?? null);

        $quantity = min($data['quantity'], $available);

        if ($quantity <= 0) {
            $this->forgetLine($request, $product, $variant?->id);
        } else {
            $request->user()->cartItems()->updateOrCreate(
                ['product_id' => $product->id, 'product_variant_id' => $variant?->id],
                ['quantity' => $quantity]
            );
        }

        return $this->respondWithItems($request);
    }

    public function destroy(Request $request, Product $product)
    {
        $variantId = $request->integer('product_variant_id') ?: null;

        $this->forgetLine($request, $product, $variantId);

        return $this->respondWithItems($request);
    }

    public function clear(Request $request)
    {
        $request->user()->cartItems()->delete();

        return response()->json(['items' => []]);
    }

    private function forgetLine(Request $request, Product $product, ?int $variantId): void
    {
        $request->user()->cartItems()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variantId)
            ->delete();
    }

    /**
     * Quale scorta vale per questa riga: se il prodotto ha varianti attive la variante è
     * obbligatoria e conta la sua disponibilità; altrimenti quella del prodotto.
     *
     * @return array{0: ProductVariant|null, 1: int}
     */
    private function resolveStock(Product $product, ?int $variantId): array
    {
        if ($product->activeVariants->isEmpty()) {
            return [null, (int) $product->stock];
        }

        if (! $variantId) {
            throw ValidationException::withMessages([
                'product_variant_id' => ['Scegli una variante ('.($product->variant_label ?: 'variante').').'],
            ]);
        }

        $variant = $product->activeVariants->firstWhere('id', $variantId);

        if (! $variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => ['Questa variante non è più disponibile.'],
            ]);
        }

        return [$variant, (int) $variant->stock];
    }

    private function respondWithItems(Request $request)
    {
        $items = $request->user()->cartItems()->with(['product.category', 'product.activeVariants'])->get();

        return response()->json([
            'items' => $items->map(fn ($item) => [
                'product' => (new ProductResource($item->product))->toArray($request),
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ]),
        ]);
    }
}
