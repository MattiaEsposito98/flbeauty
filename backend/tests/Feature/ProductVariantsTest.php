<?php

use App\Models\Category;
use App\Models\Comune;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $this->user = User::factory()->create();
    $comune = Comune::forceCreate(['name' => 'Napoli', 'latitude' => 40.85, 'longitude' => 14.26]);
    $this->address = $this->user->addresses()->create([
        'recipient_name' => 'Giulia Rossi', 'phone' => '3330000000', 'address_line' => 'Via Roma 1',
        'comune_id' => $comune->id, 'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
    ]);
    $this->rate = ShippingRate::create(['name' => 'Campania', 'type' => 'regione', 'price' => 5, 'is_active' => true]);

    $category = Category::create(['name' => 'Trucco']);
    $this->lipstick = Product::create([
        'category_id' => $category->id, 'name' => 'Rossetto matte', 'slug' => 'rossetto-matte',
        'price' => 12.50, 'stock' => 0, 'is_active' => true, 'variant_label' => 'Colore',
    ]);
    $this->red = ProductVariant::create(['product_id' => $this->lipstick->id, 'name' => 'Rosso', 'stock' => 1, 'sort_order' => 1]);
    $this->blue = ProductVariant::create(['product_id' => $this->lipstick->id, 'name' => 'Blu', 'stock' => 1, 'price' => 14, 'sort_order' => 2]);
});

function placeOrder($test, array $items, array $extra = [])
{
    return $test->actingAs($test->user)->postJson('/api/orders', array_merge([
        'address_id' => $test->address->id,
        'shipping_rate_id' => $test->rate->id,
        'items' => $items,
    ], $extra));
}

it('somma le scorte delle varianti nel prodotto', function () {
    expect($this->lipstick->fresh()->stock)->toBe(2);

    $this->red->update(['stock' => 5]);
    expect($this->lipstick->fresh()->stock)->toBe(6);

    $this->blue->update(['is_active' => false]);
    expect($this->lipstick->fresh()->stock)->toBe(5);

    $this->blue->delete();
    expect($this->lipstick->fresh()->stock)->toBe(5);
});

it('mostra le varianti con scorta e prezzo nell\'API del prodotto', function () {
    $data = $this->getJson('/api/products/rossetto-matte')->assertOk()->json('data');

    expect($data['has_variants'])->toBeTrue()
        ->and($data['variant_label'])->toBe('Colore')
        ->and($data['stock'])->toBe(2)
        ->and(collect($data['variants'])->pluck('name')->all())->toBe(['Rosso', 'Blu'])
        ->and($data['variants'][0]['price'])->toEqual(12.5)   // prezzo del prodotto
        ->and($data['variants'][1]['price'])->toEqual(14)   // prezzo suo
        ->and($data['variants'][1]['in_stock'])->toBeTrue();

    $this->red->update(['is_active' => false]);

    expect(collect($this->getJson('/api/products/rossetto-matte')->json('data.variants'))->pluck('name')->all())->toBe(['Blu']);
});

it('scala solo la scorta della variante ordinata', function () {
    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $this->red->id, 'quantity' => 1]])
        ->assertCreated();

    expect($this->red->fresh()->stock)->toBe(0)
        ->and($this->blue->fresh()->stock)->toBe(1)
        ->and($this->lipstick->fresh()->stock)->toBe(1);

    $item = Order::first()->items()->first();

    expect($item->product_variant_id)->toBe($this->red->id)
        ->and($item->variant_name)->toBe('Rosso')
        ->and((float) $item->unit_price)->toBe(12.5);
});

it('usa il prezzo della variante quando ne ha uno suo', function () {
    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $this->blue->id, 'quantity' => 1]])
        ->assertCreated();

    expect((float) Order::first()->items()->first()->unit_price)->toBe(14.0)
        ->and((float) Order::first()->total)->toBe(19.0); // 14 + 5 di spedizione
});

it('rifiuta una variante esaurita anche se un\'altra è disponibile', function () {
    $this->red->update(['stock' => 0]);

    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $this->red->id, 'quantity' => 1]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('items');

    expect(Order::count())->toBe(0)->and($this->blue->fresh()->stock)->toBe(1);
});

it('non accetta ordini senza variante o con una variante di un altro prodotto', function () {
    placeOrder($this, [['product_id' => $this->lipstick->id, 'quantity' => 1]])
        ->assertStatus(422)->assertJsonValidationErrors('items');

    $other = Product::create(['name' => 'Crema', 'slug' => 'crema', 'price' => 9, 'stock' => 5, 'is_active' => true]);
    $stranger = ProductVariant::create(['product_id' => $other->id, 'name' => 'Rosa', 'stock' => 5]);

    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $stranger->id, 'quantity' => 1]])
        ->assertStatus(422)->assertJsonValidationErrors('items');

    expect(Order::count())->toBe(0);
});

it('rifiuta due righe con lo stesso prodotto e la stessa variante, ma ammette varianti diverse', function () {
    $same = ['product_id' => $this->lipstick->id, 'product_variant_id' => $this->red->id, 'quantity' => 1];

    placeOrder($this, [$same, $same])->assertStatus(422)->assertJsonValidationErrors('items');

    placeOrder($this, [
        $same,
        ['product_id' => $this->lipstick->id, 'product_variant_id' => $this->blue->id, 'quantity' => 1],
    ])->assertCreated();

    expect($this->lipstick->fresh()->stock)->toBe(0);
});

it('restituisce la scorta alla variante giusta quando l\'ordine viene annullato e la riscala se riattivato', function () {
    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $this->blue->id, 'quantity' => 1]])
        ->assertCreated();

    $order = Order::first();
    expect($this->blue->fresh()->stock)->toBe(0)->and($this->red->fresh()->stock)->toBe(1);

    $order->update(['status' => Order::STATUS_CANCELLED]);
    expect($this->blue->fresh()->stock)->toBe(1)->and($this->red->fresh()->stock)->toBe(1)
        ->and($this->lipstick->fresh()->stock)->toBe(2);

    $order->update(['status' => 'nuovo']);
    expect($this->blue->fresh()->stock)->toBe(0)->and($this->lipstick->fresh()->stock)->toBe(1);

    $order->delete();
    expect($this->blue->fresh()->stock)->toBe(1)->and($this->lipstick->fresh()->stock)->toBe(2);
});

it('spostare una riga su un\'altra variante riallinea le due scorte', function () {
    placeOrder($this, [['product_id' => $this->lipstick->id, 'product_variant_id' => $this->red->id, 'quantity' => 1]])
        ->assertCreated();

    Order::first()->items()->first()->update(['product_variant_id' => $this->blue->id]);

    expect($this->red->fresh()->stock)->toBe(1)
        ->and($this->blue->fresh()->stock)->toBe(0)
        ->and(Order::first()->items()->first()->variant_name)->toBe('Blu');
});

it('i prodotti senza varianti funzionano come prima', function () {
    $plain = Product::create(['name' => 'Crema', 'slug' => 'crema', 'price' => 9, 'stock' => 4, 'is_active' => true]);

    placeOrder($this, [['product_id' => $plain->id, 'quantity' => 3]])->assertCreated();

    expect($plain->fresh()->stock)->toBe(1);

    placeOrder($this, [['product_id' => $plain->id, 'quantity' => 2]])->assertStatus(422);
});

it('il carrello tiene separate le varianti e le limita alla loro scorta', function () {
    $this->actingAs($this->user);

    $this->postJson('/api/cart', ['product_id' => $this->lipstick->id, 'quantity' => 1])
        ->assertStatus(422)->assertJsonValidationErrors('product_variant_id');

    $this->postJson('/api/cart', ['product_id' => $this->lipstick->id, 'product_variant_id' => $this->red->id, 'quantity' => 5])
        ->assertOk()->assertJsonPath('items.0.quantity', 1);

    $this->postJson('/api/cart', ['product_id' => $this->lipstick->id, 'product_variant_id' => $this->blue->id, 'quantity' => 1])
        ->assertOk()->assertJsonCount(2, 'items');

    $this->patchJson("/api/cart/{$this->lipstick->id}", ['product_variant_id' => $this->blue->id, 'quantity' => 9])
        ->assertOk();

    expect($this->user->cartItems()->pluck('quantity', 'product_variant_id')->all())
        ->toBe([$this->red->id => 1, $this->blue->id => 1]);

    $this->deleteJson("/api/cart/{$this->lipstick->id}?product_variant_id={$this->red->id}")
        ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.product_variant_id', $this->blue->id);
});

it('la disponibilità per il carrello include le varianti', function () {
    $data = $this->getJson("/api/products/availability?ids={$this->lipstick->id}")->assertOk()->json('data');

    expect($data[0]['variants'])->toHaveCount(2)
        ->and($data[0]['variants'][0]['stock'])->toBe(1);
});
