<?php

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->makeup = Category::create(['name' => 'Trucco']);
    $this->yara = Brand::create(['name' => 'Yara']);

    $this->gloss = Product::create(['category_id' => $this->makeup->id, 'brand_id' => $this->yara->id, 'name' => 'Lip gloss', 'price' => 3, 'stock' => 20, 'is_active' => true, 'images' => ['a.jpg']]);
    $this->lipstick = Product::create(['category_id' => $this->makeup->id, 'name' => 'Rossetto', 'price' => 8, 'stock' => 0, 'is_active' => true, 'images' => ['b.jpg']]);
    ProductVariant::create(['product_id' => $this->lipstick->id, 'name' => 'Corallo', 'stock' => 3, 'is_active' => true]);
    $this->cream = Product::create(['name' => 'Crema viso', 'price' => 12, 'stock' => 0, 'is_active' => false, 'images' => []]);
});

it('searches by name, brand, category and variant name', function () {
    Livewire::test(ListProducts::class)
        ->searchTable('yara')->assertCanSeeTableRecords([$this->gloss])->assertCanNotSeeTableRecords([$this->lipstick, $this->cream])
        ->searchTable('corallo')->assertCanSeeTableRecords([$this->lipstick])->assertCanNotSeeTableRecords([$this->gloss])
        ->searchTable('trucco')->assertCanSeeTableRecords([$this->gloss, $this->lipstick])->assertCanNotSeeTableRecords([$this->cream])
        ->searchTable('crema')->assertCanSeeTableRecords([$this->cream])->assertCountTableRecords(1);
});

it('has quick tabs for sold out, low stock and hidden products', function () {
    Livewire::test(ListProducts::class)
        ->set('activeTab', 'esauriti')->assertCanSeeTableRecords([$this->cream])->assertCanNotSeeTableRecords([$this->gloss, $this->lipstick])
        ->set('activeTab', 'scorte-basse')->assertCanSeeTableRecords([$this->lipstick])->assertCountTableRecords(1)
        ->set('activeTab', 'nascosti')->assertCanSeeTableRecords([$this->cream])->assertCountTableRecords(1);
});

it('filters by brand, variants and missing data', function () {
    Livewire::test(ListProducts::class)
        ->filterTable('brand_id', [$this->yara->id])->assertCanSeeTableRecords([$this->gloss])->assertCountTableRecords(1)
        ->resetTableFilters()
        ->filterTable('has_variants', true)->assertCanSeeTableRecords([$this->lipstick])->assertCountTableRecords(1)
        ->resetTableFilters()
        ->filterTable('incomplete', 'no_images')->assertCanSeeTableRecords([$this->cream])->assertCountTableRecords(1)
        ->resetTableFilters()
        ->filterTable('incomplete', 'no_brand')->assertCanSeeTableRecords([$this->lipstick, $this->cream])->assertCountTableRecords(2);
});

it('counts sold pieces excluding cancelled orders', function () {
    $order = Order::factory()->create(['status' => 'nuovo']);
    $order->items()->create(['product_id' => $this->gloss->id, 'quantity' => 4, 'unit_price' => 3]);
    $cancelled = Order::factory()->create(['status' => Order::STATUS_CANCELLED]);
    $cancelled->items()->create(['product_id' => $this->gloss->id, 'quantity' => 9, 'unit_price' => 3]);

    Livewire::test(ListProducts::class)->assertTableColumnStateSet('sold_quantity', 4, $this->gloss);
});

it('updates stock inline only for products without variants', function () {
    Livewire::test(ListProducts::class)
        ->call('updateTableColumnState', 'stock', (string) $this->gloss->getKey(), 7);

    expect($this->gloss->fresh()->stock)->toBe(7);
});

it('bulk hides, shows and changes brand and category', function () {
    $records = [$this->gloss, $this->lipstick];
    $other = Category::create(['name' => 'Labbra']);

    Livewire::test(ListProducts::class)
        ->selectTableRecords($records)->callAction(TestAction::make('deactivate')->table()->bulk())
        ->selectTableRecords($records)->callAction(TestAction::make('changeBrand')->table()->bulk(), ['brand_id' => $this->yara->id])
        ->selectTableRecords($records)->callAction(TestAction::make('changeCategory')->table()->bulk(), ['category_id' => $other->id]);

    foreach ($records as $product) {
        $fresh = $product->fresh();
        expect($fresh->is_active)->toBeFalse()
            ->and($fresh->brand_id)->toBe($this->yara->id)
            ->and($fresh->category_id)->toBe($other->id);
    }

    Livewire::test(ListProducts::class)
        ->selectTableRecords($records)->callAction(TestAction::make('changeBrand')->table()->bulk(), ['brand_id' => null]);

    expect($this->gloss->fresh()->brand_id)->toBeNull();
});
