<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Ordine "mescolato" dei prodotti per la home: ogni pagina ha circa `featured_ratio` (60%) di
 * prodotti della categoria in evidenza (Cosmetica) e il resto casuale dalle altre.
 *
 * L'ordine dipende dal `seed` (scelto dal browser a ogni nuova visita): con lo stesso seed
 * tutte le pagine sono coerenti (niente doppioni, niente prodotti persi, il tasto «indietro»
 * non cambia nulla); con un seed nuovo l'ordine cambia. Si calcola sugli id (numeri) di tutti
 * i prodotti attivi: costa pochi millisecondi anche con migliaia di prodotti.
 */
class HomeMix
{
    /**
     * @return list<int>|null  tutti gli id dei prodotti attivi nell'ordine della home,
     *                         oppure null se la categoria in evidenza non esiste (ordine normale)
     */
    public static function orderedIds(string $seed, int $perPage): ?array
    {
        $featured = Category::where('slug', config('shop.featured_category_slug'))->first();

        if (! $featured) {
            return null;
        }

        // Una sola lettura (id → categoria) divisa in PHP: con la condizione «categoria diversa» in SQL
        // MySQL non usa l'indice ed è 10 volte più lento.
        $featuredIds = [];
        $otherIds = [];
        foreach (Product::where('is_active', true)->orderBy('id')->pluck('category_id', 'id') as $id => $categoryId) {
            if ($categoryId === $featured->id) {
                $featuredIds[] = $id;
            } else {
                $otherIds[] = $id;
            }
        }

        $featuredIds = self::shuffle($featuredIds, $seed.'|featured');
        $otherIds = self::shuffle($otherIds, $seed.'|others');

        $perPageFeatured = (int) round($perPage * (float) config('shop.featured_ratio'));
        $sequence = [];

        for ($page = 1; $featuredIds !== [] || $otherIds !== []; $page++) {
            // Quota di cosmetica; il resto è «altro». Se manca l'uno o l'altro, l'altro riempie la pagina.
            $takeFeatured = min(count($featuredIds), $perPageFeatured);
            $takeOthers = min(count($otherIds), $perPage - $takeFeatured);
            $takeFeatured = min(count($featuredIds), $perPage - $takeOthers);

            $chunk = [
                ...array_splice($featuredIds, 0, $takeFeatured),
                ...array_splice($otherIds, 0, $takeOthers),
            ];

            // Dentro la pagina i due gruppi si mescolano, così la cosmetica non sta tutta in cima.
            array_push($sequence, ...self::shuffle($chunk, $seed.'|page'.$page));
        }

        return $sequence;
    }

    /** Mescolamento ripetibile: stesso seed, stesso risultato. */
    private static function shuffle(array $items, string $seed): array
    {
        return (new Randomizer(new Xoshiro256StarStar(hash('sha256', $seed, true))))->shuffleArray($items);
    }
}
