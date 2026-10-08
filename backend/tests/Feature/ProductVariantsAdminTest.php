<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Repeater::fake();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->category = Category::create(['name' => 'Trucco']);
});

it('crea un prodotto con varianti: la quantità del prodotto è la somma delle varianti', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Rossetto matte',
            'category_id' => $this->category->id,
            'price' => 12.5,
            'stock' => 0,
            'variant_label' => 'Colore',
            'is_active' => true,
            'variants' => [
                ['name' => 'Rosso', 'stock' => 3, 'price' => null, 'is_active' => true],
                ['name' => 'Verde', 'stock' => 2, 'price' => 14, 'is_active' => true],
                ['name' => 'Giallo', 'stock' => 5, 'price' => null, 'is_active' => false],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('name', 'Rossetto matte')->firstOrFail();

    expect($product->variant_label)->toBe('Colore')
        ->and($product->variants()->pluck('name')->all())->toBe(['Rosso', 'Verde', 'Giallo'])
        ->and($product->stock)->toBe(5);   // 3 + 2: la variante nascosta non conta
});

it('rifiuta due varianti con lo stesso nome', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Rossetto', 'price' => 10, 'is_active' => true,
            'variants' => [
                ['name' => 'Rosso', 'stock' => 1, 'is_active' => true],
                ['name' => 'Rosso', 'stock' => 1, 'is_active' => true],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Product::count())->toBe(0);
});

it('un prodotto senza varianti continua a usare la sua quantità', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm(['name' => 'Crema', 'price' => 9, 'stock' => 7, 'is_active' => true, 'variants' => []])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('name', 'Crema')->first()->stock)->toBe(7);
});

it('modificando le quantità delle varianti il totale si aggiorna e le altre varianti restano', function () {
    $product = Product::create([
        'category_id' => $this->category->id, 'name' => 'Bagnoschiuma', 'slug' => 'bagnoschiuma',
        'price' => 6, 'stock' => 0, 'is_active' => true, 'variant_label' => 'Profumo',
    ]);
    $ciliegia = ProductVariant::create(['product_id' => $product->id, 'name' => 'Ciliegia', 'stock' => 4, 'sort_order' => 1]);
    $cioccolato = ProductVariant::create(['product_id' => $product->id, 'name' => 'Cioccolato', 'stock' => 6, 'sort_order' => 2]);

    expect($product->fresh()->stock)->toBe(10);

    $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
    $state = collect($component->get('data.variants'))->map(function ($row) {
        if (($row['name'] ?? null) === 'Ciliegia') {
            $row['stock'] = 1;
        }

        return $row;
    })->all();

    $component->fillForm(['variants' => $state])->call('save')->assertHasNoFormErrors();

    expect($ciliegia->fresh()->stock)->toBe(1)
        ->and($cioccolato->fresh()->stock)->toBe(6)
        ->and($product->fresh()->stock)->toBe(7);
});
