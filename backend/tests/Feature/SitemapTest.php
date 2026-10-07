<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.frontend_url' => 'https://flbeauty.it']);
});

it('elenca home, categorie con prodotti e prodotti attivi con gli indirizzi del sito', function () {
    $withProducts = Category::factory()->create(['slug' => 'rossetti', 'is_active' => true]);
    Category::factory()->create(['slug' => 'vuota', 'is_active' => true]);
    Category::factory()->create(['slug' => 'nascosta', 'is_active' => false]);

    Product::factory()->create([
        'slug' => 'rossetto-rosa',
        'is_active' => true,
        'category_id' => $withProducts->id,
        'images' => ['products/images/rossetto.jpg'],
    ]);
    Product::factory()->create(['slug' => 'disattivato', 'is_active' => false]);

    $xml = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->getContent();

    expect($xml)
        ->toContain('<loc>https://flbeauty.it/</loc>')
        ->toContain('<loc>https://flbeauty.it/categoria/rossetti</loc>')
        ->toContain('<loc>https://flbeauty.it/prodotti/rossetto-rosa</loc>')
        ->toContain('products/images/rossetto.jpg')
        ->toContain('<loc>https://flbeauty.it/privacy</loc>')
        ->not->toContain('categoria/vuota')
        ->not->toContain('categoria/nascosta')
        ->not->toContain('disattivato');
});

it('scrive il file con il comando sitemap:generate', function () {
    $path = sys_get_temp_dir().'/flbeauty-sitemap-'.uniqid().'.xml';
    config(['app.sitemap_path' => $path]);

    Product::factory()->create(['slug' => 'crema', 'is_active' => true]);

    expect(Artisan::call('sitemap:generate'))->toBe(0)
        ->and(file_get_contents($path))->toContain('https://flbeauty.it/prodotti/crema');

    unlink($path);
});

it('tiene il dominio del backend fuori da Google', function () {
    $this->get('/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
