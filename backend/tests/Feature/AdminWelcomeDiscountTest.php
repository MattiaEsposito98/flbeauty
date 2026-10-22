<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function welcomeFor(User $user, array $overrides = []): Discount
{
    return Discount::create([
        'code' => 'BENVENUTO-'.strtoupper(substr(md5((string) $user->id.microtime()), 0, 6)),
        'kind' => Discount::KIND_WELCOME,
        'user_id' => $user->id,
        'type' => 'percentuale',
        'value' => 15,
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
        'is_active' => true,
        ...$overrides,
    ]);
}

function welcomeOrderData(User $customer, ?int $discountId, int $productId): array
{
    return [
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'user_id' => $customer->id,
        'status' => 'nuovo',
        'shipping_cost' => 0,
        'discount_id' => $discountId,
        'items' => [['product_id' => $productId, 'quantity' => 1, 'unit_price' => 100]],
    ];
}

beforeEach(function () {
    Mail::fake();
    Repeater::fake();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->customer = User::factory()->create();
    $this->product = Product::create([
        'category_id' => Category::create(['name' => 'Trucco'])->id,
        'name' => 'Rossetto', 'slug' => 'rossetto', 'price' => 100, 'stock' => 10, 'is_active' => true,
    ]);
});

it('permette all\'admin di usare il codice di benvenuto del cliente scelto', function () {
    $welcome = welcomeFor($this->customer);

    Livewire::test(CreateOrder::class)
        ->fillForm(welcomeOrderData($this->customer, $welcome->id, $this->product->id))
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::sole();
    expect($order->discount_id)->toBe($welcome->id)
        ->and((float) $order->total)->toBe(85.0);
});

it('rifiuta il codice di benvenuto di un altro cliente', function () {
    $other = User::factory()->create();
    $welcome = welcomeFor($other);

    Livewire::test(CreateOrder::class)
        ->fillForm(welcomeOrderData($this->customer, $welcome->id, $this->product->id))
        ->call('create')
        ->assertHasFormErrors(['discount_id']);

    expect(Order::count())->toBe(0);
});

it('rifiuta un codice di benvenuto già usato o scaduto', function () {
    $used = welcomeFor($this->customer);
    Order::create(['customer_name' => 'X', 'customer_email' => 'x@example.test', 'status' => 'nuovo', 'total' => 10, 'discount_id' => $used->id]);

    Livewire::test(CreateOrder::class)
        ->fillForm(welcomeOrderData($this->customer, $used->id, $this->product->id))
        ->call('create')
        ->assertHasFormErrors(['discount_id']);

    $expired = welcomeFor(User::factory()->create(), ['ends_at' => now()->subDay()]);
    $other = User::find($expired->user_id);

    Livewire::test(CreateOrder::class)
        ->fillForm(welcomeOrderData($other, $expired->id, $this->product->id))
        ->call('create')
        ->assertHasFormErrors(['discount_id']);
});

it('rifiuta il codice di benvenuto su un ordine da ospite', function () {
    $welcome = welcomeFor($this->customer);

    Livewire::test(CreateOrder::class)
        ->fillForm([...welcomeOrderData($this->customer, $welcome->id, $this->product->id), 'user_id' => null, 'customer_email' => 'ospite@example.test'])
        ->call('create')
        ->assertHasFormErrors(['discount_id']);
});
