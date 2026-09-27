<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->whereHas('wishlistItems', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('category')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products);
    }

    public function store(Request $request, Product $product)
    {
        $request->user()->wishlistItems()->firstOrCreate(['product_id' => $product->id]);

        return response()->json(['message' => 'Aggiunto ai preferiti.'], 201);
    }

    public function destroy(Request $request, Product $product)
    {
        $request->user()->wishlistItems()->where('product_id', $product->id)->delete();

        return response()->json(['message' => 'Rimosso dai preferiti.']);
    }
}
