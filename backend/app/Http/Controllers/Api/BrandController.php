<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Le marche che hanno almeno un prodotto attivo, per il filtro del catalogo.
     * Con `?category=slug` solo quelle presenti in quella categoria.
     */
    public function index(Request $request)
    {
        $brands = Brand::query()
            ->whereHas('products', function ($query) use ($request) {
                $query->where('is_active', true)
                    ->when($request->query('category'), fn ($q, $slug) => $q->whereHas(
                        'category',
                        fn ($c) => $c->where('slug', $slug)
                    ));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json(['data' => $brands]);
    }
}
