<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($request->query('category'), fn ($query, $slug) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $slug)
            ))
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(12);

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product->load('category'));
    }
}
