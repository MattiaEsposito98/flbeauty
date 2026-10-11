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
            ->with(['category', 'brand', 'activeVariants'])
            ->where('is_active', true)
            ->when($request->query('category'), fn ($query, $slug) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $slug)
            ))
            ->when($request->query('brand'), fn ($query, $slug) => $query->whereHas(
                'brand',
                fn ($q) => $q->where('slug', $slug)
            ))
            ->when($request->query('search'), fn ($query, $search) => $this->applySearch($query, $search))
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
            ->with(['category', 'activeVariants'])
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product->load(['category', 'brand', 'activeVariants']));
    }

    /**
     * Ricerca del catalogo: ogni parola digitata deve comparire nel nome o nella descrizione del
     * prodotto, oppure nella sua marca, categoria o in una variante attiva (es. «yara», «labbra»,
     * «corallo»). In cima i prodotti il cui nome contiene la frase cercata.
     */
    private function applySearch($query, string $search): void
    {
        $words = $this->searchWords($search);

        if ($words === []) {
            return;
        }

        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';

            $query->where(function ($q) use ($word, $like) {
                $q->where(fn ($text) => $this->matchesText($text, $word, $like))
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('activeVariants', fn ($v) => $v->where('name', 'like', $like));
            });
        }

        $query->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ['%'.$this->escapeLike(implode(' ', $words)).'%']);
    }

    /**
     * Nome/descrizione: su MySQL usa l'indice FULLTEXT in modalità boolean con la parola come
     * prefisso ("+parola*": parole intere o inizi di parola, non pezzi a metà parola — limite noto
     * di FULLTEXT); altrove (test con SQLite) ricade su un semplice «contiene».
     */
    private function matchesText($query, string $word, string $like): void
    {
        if ($query->getConnection()->getDriverName() === 'mysql') {
            $query->whereFullText(['name', 'description'], '+'.$word.'*', ['mode' => 'boolean']);

            return;
        }

        $query->where('name', 'like', $like)->orWhere('description', 'like', $like);
    }

    /** @return list<string> */
    private function searchWords(string $search): array
    {
        // Caratteri con significato speciale in modalità boolean: rimossi per evitare errori di
        // sintassi ed evitare che l'utente li usi come operatori.
        $sanitized = preg_replace('/[+\-><()~*"@]+/', ' ', $search);

        return array_values(array_filter(preg_split('/\s+/', trim($sanitized))));
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
