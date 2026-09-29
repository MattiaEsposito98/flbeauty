<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
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

    $category = Category::create(['name' => 'Trucco']);
    $this->product = Product::create([
        'category_id' => $category->id,
        'name' => 'Rossetto',
        'slug' => 'rossetto',
        'price' => 12.50,
        'stock' => 3,
        'is_active' => true,
    ]);
});

function guestOrderData(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Giulia Ospite',
        'customer_email' => 'giulia@example.test',
        'customer_phone' => '351 745 9482',
        'status' => 'nuovo',
        'shipping_cost' => 5,
        'send_confirmation_email' => true,
        'items' => [['product_id' => test()->product->id, 'quantity' => 2, 'unit_price' => 12.50]],
    ], $overrides);
}

it('crea un ordine per un ospite, scala lo stock e manda la conferma', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(guestOrderData())
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Ordine creato');

    $order = Order::sole();
    expect($order->user_id)->toBeNull()
        ->and((float) $order->total)->toBe(30.0)
        ->and($this->product->fresh()->stock)->toBe(1);

    Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo('giulia@example.test'));
});

it('non manda la conferma se l\'interruttore è spento', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(guestOrderData(['send_confirmation_email' => false]))
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertNothingQueued();
});

it('blocca quantità oltre la disponibilità, anche su più righe dello stesso prodotto', function () {
    Livewire::test(CreateOrder::class)
        ->fillForm(guestOrderData(['items' => [
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 12.50],
            ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 12.50],
        ]]))
        ->call('create')
        ->assertHasFormErrors(['items.0.quantity', 'items.1.quantity']);

    expect(Order::count())->toBe(0)
        ->and($this->product->fresh()->stock)->toBe(3);
});

it('in modifica conta come disponibili i pezzi già riservati dall\'ordine', function () {
    $order = Order::create(['customer_name' => 'Giulia', 'customer_email' => 'g@example.test', 'status' => 'nuovo', 'total' => 0]);
    $order->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 12.50]);
    expect($this->product->fresh()->stock)->toBe(1);

    // 2 già riservati + 1 in magazzino = può salire fino a 3.
    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['items' => [['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 12.50]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->product->fresh()->stock)->toBe(0);
});

it('compila i dati del cliente scegliendo un account registrato', function () {
    $customer = User::factory()->create(['name' => 'Anna Rossi', 'email' => 'anna@example.test']);

    Livewire::test(CreateOrder::class)
        ->fillForm(['user_id' => $customer->id])
        ->assertSchemaStateSet(['customer_name' => 'Anna Rossi', 'customer_email' => 'anna@example.test']);
});

it('prepara il link WhatsApp con il riepilogo per il cliente', function () {
    $order = Order::create([
        'customer_name' => 'Giulia Ospite',
        'customer_email' => 'g@example.test',
        'customer_phone' => '+39 351 745 9482',
        'status' => 'nuovo',
        'shipping_cost' => 5,
        'total' => 0,
    ]);
    $order->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 12.50]);

    $url = OrderWhatsApp::url($order->fresh());
    $text = rawurldecode(explode('?text=', $url)[1]);

    expect($url)->toStartWith('https://wa.me/393517459482?text=')
        ->and($text)->toContain('Ciao Giulia!')
        ->toContain('Rossetto x2: 25,00 €')
        ->toContain('*Totale: 30,00 €*')
        ->toContain('Stato: In attesa di pagamento');
});

it('mette corriere, numero e link di tracking nel messaggio WhatsApp', function () {
    $order = Order::create([
        'customer_name' => 'giulia ospite',
        'customer_email' => 'g@example.test',
        'customer_phone' => '3517459482',
        'status' => 'evaso',
        'carrier' => 'Poste Italiane',
        'tracking_number' => '3UW14AS664236',
        // Il vecchio link generico salvato dal form va scartato.
        'tracking_url' => Order::GENERIC_TRACKING_PAGE,
        'total' => 0,
    ]);

    expect($order->fresh()->tracking_url)->toBeNull();

    $text = OrderWhatsApp::message($order->fresh());

    expect($text)->toContain('Ciao Giulia!')
        ->toContain('Corriere: Poste Italiane')
        ->toContain('Numero di tracking: 3UW14AS664236')
        ->toContain('Segui la spedizione: https://business.poste.it/grandi-imprese/cerca-spedizioni/index.html#/risultati-spedizioni/3UW14AS664236');
});

it('riconosce i numeri di telefono italiani', function (?string $input, ?string $expected) {
    expect(OrderWhatsApp::normalizePhone($input))->toBe($expected);
})->with([
    ['351 745 9482', '393517459482'],
    ['+39 351 7459482', '393517459482'],
    ['0039 3517459482', '393517459482'],
    ['081 1234567', '390811234567'],
    ['12345', null],
    [null, null],
]);
