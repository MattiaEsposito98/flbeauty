<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return $this->respondWithItems($request);
    }

    /**
     * Aggiunge una quantità al carrello: se il prodotto è già presente,
     * la quantità si somma a quella esistente (non la sostituisce).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        $item = $request->user()->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min(($item->exists ? $item->quantity : 0) + $data['quantity'], $product->stock);

        if ($item->quantity > 0) {
            $item->save();
        }

        return $this->respondWithItems($request);
    }

    /**
     * Imposta la quantità esatta per un prodotto (usato dall'input numerico
     * del carrello). Una quantità <= 0 rimuove la riga.
     */
    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer'],
        ]);

        $quantity = min($data['quantity'], $product->stock);

        if ($quantity <= 0) {
            $request->user()->cartItems()->where('product_id', $product->id)->delete();
        } else {
            $request->user()->cartItems()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $quantity]
            );
        }

        return $this->respondWithItems($request);
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()->cartItems()->where('product_id', $product->id)->delete();

        return $this->respondWithItems($request);
    }

    public function clear(Request $request)
    {
        $request->user()->cartItems()->delete();

        return response()->json(['items' => []]);
    }

    private function respondWithItems(Request $request)
    {
        $items = $request->user()->cartItems()->with('product.category')->get();

        return response()->json([
            'items' => $items->map(fn ($item) => [
                'product' => (new ProductResource($item->product))->toArray($request),
                'quantity' => $item->quantity,
            ]),
        ]);
    }
}
