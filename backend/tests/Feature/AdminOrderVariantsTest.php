<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\OrderWhatsApp;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Repeater::fake();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->bath = Product::create(['name' => 'Bagnoschiuma', 'slug' => 'bagnoschiuma', 'price' => 6, 'stock' => 0, 'is_active' => true, 'variant_label' => 'Profumo']);
    $this->cherry = ProductVariant::create(['product_id' => $this->bath->id, 'name' => 'Ciliegia', 'stock' => 2, 'sort_order' => 1]);
    $this->choco = ProductVariant::create(['product_id' => $this->bath->id, 'name' => 'Cioccolato', 'stock' => 3, 'price' => 7, 'sort_order' => 2]);
});

function orderWithVariants(array $items, array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Giulia Ospite',
        'customer_email' => 'giulia@example.test',
        'customer_phone' => '351 745 9482',
        'status' => 'nuovo',
        'shipping_cost' => 5,
        'send_confirmation_email' => false,
        'items' => $items,
    ], $overrides);
}

it('crea un ordine con una variante e scala solo quella', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(orderWithVariants([
            ['product_id' => $this->bath->id, 'product_variant_id' => $this->choco->id, 'quantity' => 2, 'unit_price' => 7],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $item = Order::sole()->items()->sole();

    expect($item->variant_name)->toBe('Cioccolato')
        ->and($this->choco->fresh()->stock)->toBe(1)
        ->and($this->cherry->fresh()->stock)->toBe(2)
        ->and($this->bath->fresh()->stock)->toBe(3)
        ->and($item->displayName())->toBe('Bagnoschiuma – Cioccolato');
});

it('chiede la variante quando il prodotto ne ha', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(orderWithVariants([['product_id' => $this->bath->id, 'quantity' => 1, 'unit_price' => 6]]))
        ->call('create')
        ->assertHasFormErrors(['items.0.product_variant_id' => 'required']);

    expect(Order::count())->toBe(0);
});

it('blocca quantità oltre la scorta della variante, anche su più righe, ma non quella delle altre varianti', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(orderWithVariants([
            ['product_id' => $this->bath->id, 'product_variant_id' => $this->cherry->id, 'quantity' => 2, 'unit_price' => 6],
            ['product_id' => $this->bath->id, 'product_variant_id' => $this->cherry->id, 'quantity' => 1, 'unit_price' => 6],
        ]))
        ->call('create')
        ->assertHasFormErrors(['items.0.quantity', 'items.1.quantity']);

    expect(Order::count())->toBe(0);

    // 2 di Ciliegia + 3 di Cioccolato: ognuna dentro la propria scorta
    Livewire::test(CreateOrder::class)
        ->fillForm(orderWithVariants([
            ['product_id' => $this->bath->id, 'product_variant_id' => $this->cherry->id, 'quantity' => 2, 'unit_price' => 6],
            ['product_id' => $this->bath->id, 'product_variant_id' => $this->choco->id, 'quantity' => 3, 'unit_price' => 7],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect($this->bath->fresh()->stock)->toBe(0);
});

it('in modifica conta come disponibili i pezzi già riservati dalla variante e annullando li restituisce', function () {
    $order = Order::create(['customer_name' => 'Giulia', 'customer_email' => 'g@example.test', 'status' => 'nuovo', 'total' => 0]);
    $order->items()->create(['product_id' => $this->bath->id, 'product_variant_id' => $this->cherry->id, 'quantity' => 2, 'unit_price' => 6]);
    expect($this->cherry->fresh()->stock)->toBe(0);

    // già 2 riservati e 0 in magazzino: salire a 3 non si può (servirebbe 1 in più)
    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['items' => [['product_id' => $this->bath->id, 'product_variant_id' => $this->cherry->id, 'quantity' => 3, 'unit_price' => 6]]])
        ->call('save')
        ->assertHasFormErrors(['items.0.quantity']);

    expect($order->items()->sole()->quantity)->toBe(2);

    // restare a 2 va bene, e annullare restituisce i pezzi alla variante giusta
    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['status' => 'annullato'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->cherry->fresh()->stock)->toBe(2)->and($this->choco->fresh()->stock)->toBe(3);
});

it('il riepilogo WhatsApp mostra il nome della variante', function () {
    $order = Order::create(['customer_name' => 'Giulia', 'customer_email' => 'g@example.test', 'customer_phone' => '351 745 9482', 'status' => 'nuovo', 'total' => 0]);
    $order->items()->create(['product_id' => $this->bath->id, 'product_variant_id' => $this->choco->id, 'quantity' => 1, 'unit_price' => 7]);

    $text = rawurldecode(explode('?text=', OrderWhatsApp::url($order->fresh()))[1]);

    expect($text)->toContain('Bagnoschiuma – Cioccolato');
});
