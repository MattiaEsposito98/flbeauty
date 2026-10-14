<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cosmetica = Category::create(['name' => 'Cosmetica']);
    $this->parfum = Category::create(['name' => 'Parfum']);

    foreach (range(1, 30) as $i) {
        Product::create(['category_id' => $this->cosmetica->id, 'name' => sprintf('Cosmetico %02d', $i), 'price' => 5, 'stock' => 3, 'is_active' => true]);
    }
    foreach (range(1, 60) as $i) {
        Product::create(['category_id' => $this->parfum->id, 'name' => sprintf('Profumo %02d', $i), 'price' => 9, 'stock' => 3, 'is_active' => true]);
    }
    Product::create(['category_id' => $this->cosmetica->id, 'name' => 'Cosmetico nascosto', 'price' => 5, 'stock' => 3, 'is_active' => false]);

    $this->page = fn (int $page, string $seed = 'abc12345', array $extra = []) => $this->getJson('/api/products?'.http_build_query(array_merge(['per_page' => 24, 'seed' => $seed, 'page' => $page], $extra)));
    $this->ids = fn ($response) => collect($response->json('data'))->pluck('id')->all();
    $this->cosmeticIds = Product::where('category_id', $this->cosmetica->id)->pluck('id')->all();
});

it('puts about 60% featured category products in each page', function () {
    $response = ($this->page)(1)->assertOk()->assertJsonCount(24, 'data');

    $featured = collect(($this->ids)($response))->intersect($this->cosmeticIds)->count();
    expect($featured)->toBe(14);   // 60% di 24 = 14,4 → 14; gli altri 10 sono di altre categorie
});

it('is consistent across pages: no duplicates, no missing products, hidden ones excluded', function () {
    $all = [];
    foreach (range(1, 4) as $page) {
        $all = array_merge($all, ($this->ids)(($this->page)($page)));
    }

    expect($all)->toHaveCount(90)                       // 30 + 60 attivi
        ->and(array_unique($all))->toHaveCount(90)
        ->and(($this->page)(1)->json('meta.total'))->toBe(90)
        ->and(($this->page)(1)->json('meta.last_page'))->toBe(4)
        ->and(($this->page)(5)->json('data'))->toBe([]);
});

it('keeps the same order for the same seed and changes it with a different seed', function () {
    expect(($this->ids)(($this->page)(1)))->toBe(($this->ids)(($this->page)(1)))
        ->and(($this->ids)(($this->page)(1, 'other9999')))->not->toBe(($this->ids)(($this->page)(1)));
});

it('fills later pages with the other categories when the featured one runs out', function () {
    // 30 cosmetici a 14 per pagina: finiscono alla pagina 3 (14+14+2); la pagina 4 è tutta «altro».
    $page4 = ($this->ids)(($this->page)(4));

    expect(collect($page4)->intersect($this->cosmeticIds)->count())->toBe(0)
        ->and($page4)->toHaveCount(90 - 24 * 3);
});

it('does not mix the products when there is no seed, a filter or a search', function () {
    $alphabetical = fn ($response) => collect($response->json('data'))->pluck('name')->all();

    $noSeed = $this->getJson('/api/products?per_page=24');
    expect($alphabetical($noSeed)[0])->toBe('Cosmetico 01');

    $filtered = $this->getJson('/api/products?per_page=24&seed=abc12345&category=parfum');
    expect($alphabetical($filtered)[0])->toBe('Profumo 01');

    $search = $this->getJson('/api/products?per_page=24&seed=abc12345&search=cosmetico');
    expect($alphabetical($search)[0])->toBe('Cosmetico 01');
});

it('ignores an invalid seed and falls back to the normal order', function () {
    $response = $this->getJson('/api/products?per_page=24&seed='.urlencode('ab c;'))->assertOk();

    expect(collect($response->json('data'))->pluck('name')->first())->toBe('Cosmetico 01');
});

it('falls back to the normal order when the featured category does not exist', function () {
    config(['shop.featured_category_slug' => 'non-esiste']);

    expect(collect(($this->page)(1)->json('data'))->pluck('name')->first())->toBe('Cosmetico 01');
});

it('limits page size to 48', function () {
    $this->getJson('/api/products?per_page=500')->assertOk()->assertJsonCount(48, 'data');
    $this->getJson('/api/products')->assertOk()->assertJsonCount(12, 'data');
});
