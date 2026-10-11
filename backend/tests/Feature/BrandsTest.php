<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->makeup = Category::create(['name' => 'Trucco']);
    $this->skin = Category::create(['name' => 'Viso']);
    $this->yara = Brand::create(['name' => 'Yara']);
    $this->unused = Brand::create(['name' => 'Senza prodotti']);

    $this->gloss = Product::create([
        'category_id' => $this->makeup->id, 'brand_id' => $this->yara->id, 'name' => 'Lip gloss',
        'price' => 3, 'stock' => 5, 'is_active' => true,
    ]);
    $this->cream = Product::create([
        'category_id' => $this->skin->id, 'name' => 'Crema senza marca',
        'price' => 9, 'stock' => 5, 'is_active' => true,
    ]);
});

it('creates a unique slug for the brand', function () {
    expect($this->yara->slug)->toBe('yara');
    expect(Brand::create(['name' => 'Yara!'])->slug)->toBe('yara-2');
});

it('lists only brands that have active products, optionally per category', function () {
    $this->getJson('/api/brands')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'yara');
    $this->getJson('/api/brands?category=trucco')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/brands?category=viso')->assertOk()->assertJsonCount(0, 'data');

    $this->gloss->update(['is_active' => false]);
    $this->getJson('/api/brands')->assertOk()->assertJsonCount(0, 'data');
});

it('filters products by brand and exposes the brand (null when absent)', function () {
    $this->getJson('/api/products?brand=yara')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.brand.name', 'Yara');

    $this->getJson('/api/products')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/products/crema-senza-marca')->assertOk()->assertJsonPath('data.brand', null);
});

it('keeps the product when its brand is deleted', function () {
    $this->yara->delete();

    expect($this->gloss->fresh()->brand_id)->toBeNull();
});

it('lets the admin create a product with an optional brand, with or without one', function () {
    \Filament\Forms\Components\Repeater::fake();
    $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]));

    \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\CreateProduct::class)
        ->fillForm(['name' => 'Con marca', 'brand_id' => $this->yara->id, 'price' => 5, 'stock' => 1, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    \Livewire\Livewire::test(\App\Filament\Resources\Products\Pages\CreateProduct::class)
        ->fillForm(['name' => 'Senza marca', 'price' => 5, 'stock' => 1, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('name', 'Con marca')->first()->brand_id)->toBe($this->yara->id)
        ->and(Product::where('name', 'Senza marca')->first()->brand_id)->toBeNull();
});

it('lets the admin rename and delete a brand from the product menu', function () {
    \Filament\Forms\Components\Repeater::fake();
    $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]));
    $edit = \App\Filament\Resources\Products\Pages\EditProduct::class;

    \Livewire\Livewire::test($edit, ['record' => $this->gloss->getRouteKey()])
        ->callAction(\Filament\Actions\Testing\TestAction::make('editOption')->schemaComponent('brand_id'), ['name' => 'Yara Beauty'])
        ->assertHasNoFormErrors();

    expect($this->yara->fresh()->name)->toBe('Yara Beauty');

    \Livewire\Livewire::test($edit, ['record' => $this->gloss->getRouteKey()])
        ->callAction(\Filament\Actions\Testing\TestAction::make('deleteBrand')->schemaComponent('brand_id'))
        ->assertSchemaStateSet(['brand_id' => null]);

    expect(Brand::find($this->yara->id))->toBeNull()
        ->and($this->gloss->fresh()->brand_id)->toBeNull();
});
