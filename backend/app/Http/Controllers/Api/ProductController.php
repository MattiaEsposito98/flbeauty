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
            ->when($request->query('search'), function ($query, $search) {
                $booleanQuery = $this->toFullTextBooleanQuery($search);

                if ($booleanQuery !== '') {
                    $query->whereFullText(['name', 'description'], $booleanQuery, ['mode' => 'boolean']);
                }
            })
            ->orderBy('name')
            ->paginate(12);

        return ProductResource::collection($products);
    }

    /**
     * Dati aggiornati (stock, prezzo) dei prodotti nel carrello, per
     * ricontrollare la disponibilità prima dell'ordine. I prodotti non più
     * attivi non vengono restituiti: il client li tratta come esauriti.
     */
    public function availability(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(100);

        $products = Product::query()
            ->with('category')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product->load('category'));
    }

    /**
     * Trasforma il testo digitato dall'utente in una query per l'indice
     * FULLTEXT di MySQL in modalità boolean: ogni parola diventa un prefisso
     * obbligatorio ("+parola*"), per avvicinarsi al comportamento della
     * vecchia ricerca "contiene" pur usando l'indice (parole intere/prefissi,
     * non sottostringhe a metà parola — limite noto di FULLTEXT).
     */
    private function toFullTextBooleanQuery(string $search): string
    {
        // Caratteri con significato speciale in modalità boolean: rimossi per
        // evitare errori di sintassi ed evitare che l'utente li usi come operatori.
        $sanitized = preg_replace('/[+\-><()~*"@]+/', ' ', $search);

        $words = array_filter(preg_split('/\s+/', trim($sanitized)));

        return implode(' ', array_map(fn ($word) => '+'.$word.'*', $words));
    }
}
