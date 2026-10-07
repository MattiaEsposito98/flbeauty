<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

/**
 * Sitemap XML del sito (flbeauty.it): home, categorie con almeno un prodotto
 * attivo, prodotti attivi (con le loro foto) e pagine legali. Gli indirizzi sono
 * quelli del frontend (`FRONTEND_URL`), non del backend.
 */
class Sitemap
{
    public static function xml(): string
    {
        $base = rtrim(config('app.frontend_url'), '/');

        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ];

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $base.'/categoria/'.$category->slug,
                'priority' => '0.8',
                'changefreq' => 'daily',
            ];
        }

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($products as $product) {
            $urls[] = [
                'loc' => $base.'/prodotti/'.$product->slug,
                'lastmod' => $product->updated_at?->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'images' => collect($product->images ?? [])
                    ->map(fn (string $path) => Storage::disk('public')->url($path))
                    ->all(),
            ];
        }

        foreach (['/privacy', '/cookie'] as $path) {
            $urls[] = ['loc' => $base.$path, 'priority' => '0.2', 'changefreq' => 'yearly'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n    <loc>".self::escape($url['loc'])."</loc>\n";

            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod']."</lastmod>\n";
            }

            $xml .= '    <changefreq>'.$url['changefreq']."</changefreq>\n";
            $xml .= '    <priority>'.$url['priority']."</priority>\n";

            foreach ($url['images'] ?? [] as $image) {
                $xml .= '    <image:image><image:loc>'.self::escape($image)."</image:loc></image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        return $xml."</urlset>\n";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
