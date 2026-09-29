<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function guestOrder(string $email): Order
{
    return Order::create([
        'customer_name' => 'Giulia',
        'customer_email' => $email,
        'status' => 'evaso',
        'total' => 10,
    ]);
}

it('collega gli ordini da ospite solo dopo la verifica dell\'email', function () {
    Mail::fake();
    $order = guestOrder('Giulia@Example.test');
    $other = guestOrder('altra@example.test');

    $user = User::factory()->unverified()->create(['email' => 'giulia@example.test']);
    expect($order->fresh()->user_id)->toBeNull();

    // Link dell'email di verifica.
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);
    $this->get($url)->assertRedirect();

    expect($order->fresh()->user_id)->toBe($user->id)
        ->and($other->fresh()->user_id)->toBeNull();

    // E ora li vede nel profilo.
    $this->actingAs($user)->getJson('/api/orders')->assertOk()->assertJsonCount(1);

    Mail::assertNothingQueued();
});

it('collega gli ordini anche con il pulsante "Segna email come verificata" dell\'admin', function () {
    $order = guestOrder('mario@example.test');
    $user = User::factory()->unverified()->create(['email' => 'mario@example.test']);

    $user->markEmailAsVerified();

    expect($order->fresh()->user_id)->toBe($user->id);
});

it('non permette un ordine da ospite con l\'email di un cliente registrato', function () {
    Mail::fake();
    Repeater::fake();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $customer = User::factory()->create(['name' => 'Anna Rossi', 'email' => 'anna@example.test']);
    $product = Product::create([
        'category_id' => Category::create(['name' => 'Trucco'])->id,
        'name' => 'Rossetto', 'slug' => 'rossetto', 'price' => 10, 'stock' => 5, 'is_active' => true,
    ]);

    $data = [
        'customer_name' => 'Anna Rossi',
        'customer_email' => 'ANNA@example.test',
        'status' => 'nuovo',
        'shipping_cost' => 0,
        'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10]],
    ];

    Livewire::test(CreateOrder::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors(['customer_email']);

    expect(Order::count())->toBe(0);

    Livewire::test(CreateOrder::class)
        ->fillForm([...$data, 'user_id' => $customer->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Order::sole()->user_id)->toBe($customer->id);
});
