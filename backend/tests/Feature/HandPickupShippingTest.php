<?php

use App\Filament\Resources\ShippingRates\Pages\CreateShippingRate;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Comune;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Support\OrderWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->hand = ShippingRate::where('type', ShippingRate::TYPE_HAND)->firstOrFail();   // creata dalla migration
    $this->region = ShippingRate::create(['name' => 'Campania', 'type' => 'regione', 'price' => 7.5, 'is_active' => true]);

    $this->customer = User::factory()->create(['name' => 'Anna Rossi', 'email' => 'anna@example.test']);
    $this->address = $this->customer->addresses()->create([
        'recipient_name' => 'Anna Rossi', 'phone' => '3330000000', 'address_line' => 'Via Roma 1',
        'comune_id' => Comune::forceCreate(['name' => 'Napoli', 'latitude' => 40.85, 'longitude' => 14.26])->id,
        'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
    ]);
    $this->product = Product::create([
        'category_id' => Category::create(['name' => 'Trucco'])->id,
        'name' => 'Gloss', 'slug' => 'gloss', 'price' => 10, 'stock' => 20, 'is_active' => true,
    ]);

    $this->order = fn (ShippingRate $rate) => $this->actingAs($this->customer)->postJson('/api/orders', [
        'address_id' => $this->address->id,
        'shipping_rate_id' => $rate->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
    ])->assertCreated();
});

it('has a free "Ritiro a mano" rate available to customers right after the migration', function () {
    expect($this->hand->name)->toBe('Ritiro a mano (gratis)')
        ->and((float) $this->hand->price)->toBe(0.0)
        ->and($this->hand->is_active)->toBeTrue()
        ->and($this->hand->isHandPickup())->toBeTrue();

    $rate = collect($this->getJson('/api/shipping-rates')->assertOk()->json())->firstWhere('type', 'a_mano');
    expect($rate['name'])->toBe('Ritiro a mano (gratis)');
});

it('orders with hand pickup cost only the products', function () {
    $response = ($this->order)($this->hand);

    expect((float) $response->json('total'))->toBe(20.0)
        ->and((float) $response->json('shipping_cost'))->toBe(0.0);
});

it('does not show a shipping address in the emails and WhatsApp message for hand pickup', function () {
    $order = Order::find(($this->order)($this->hand)->json('id'));
    $html = (new OrderConfirmation($order->load('shippingRate')))->render();

    expect($html)->toContain('Ritiro a mano')->toContain('Gratis')->not->toContain('Spedizione a')->not->toContain('Via Roma 1');

    $message = OrderWhatsApp::message($order);
    expect($message)->toContain('Ritiro a mano: gratis')->not->toContain('Spedizione a:')->not->toContain('Via Roma 1');
});

it('keeps the address and shipping cost for regional shipping', function () {
    $order = Order::find(($this->order)($this->region)->json('id'));
    $html = (new OrderConfirmation($order->load('shippingRate')))->render();

    expect($html)->toContain('Spedizione a')->toContain('Via Roma 1')->not->toContain('Ritiro a mano');
    expect(OrderWhatsApp::message($order))->toContain('Spedizione a:');
});

it('lets the admin create a hand pickup rate', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(CreateShippingRate::class)
        ->fillForm(['name' => 'Ritiro in negozio', 'type' => 'a_mano', 'price' => 0, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ShippingRate::where('name', 'Ritiro in negozio')->first()->type)->toBe('a_mano');
});
