<?php

use App\Filament\Resources\Users\Actions\BlockUserActions;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function customerWithOrder(string $status = 'nuovo', int $quantity = 3): array
{
    $user = User::factory()->create(['username' => 'cliente', 'password' => 'Password123']);
    $product = Product::factory()->create(['stock' => 10]);

    $order = Order::factory()->create(['user_id' => $user->id, 'status' => $status]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => $product->price,
    ]);

    return [$user, $product->fresh(), $order];
}

it('non fa accedere un account bloccato e chiude gli accessi aperti', function () {
    $user = User::factory()->create(['username' => 'cliente', 'password' => 'Password123']);
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/user')->assertOk();

    $user->block('ordini fasulli');

    expect($user->fresh()->isBlocked())->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);

    $this->postJson('/api/login', ['login' => 'cliente', 'password' => 'Password123'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('login');

    // Con la password sbagliata non si scopre che l'account è sospeso.
    $this->postJson('/api/login', ['login' => 'cliente', 'password' => 'sbagliata'])
        ->assertJsonPath('errors.login.0', 'Le credenziali fornite non sono corrette.');

    $user->unblock();

    $this->postJson('/api/login', ['login' => 'cliente', 'password' => 'Password123'])->assertOk();
});

it('annulla gli ordini aperti e rimette i pezzi in magazzino quando blocca', function () {
    Mail::fake();
    [$user, $product, $order] = customerWithOrder('nuovo', 3);
    expect($product->stock)->toBe(7);

    $cancelled = $user->block('ordini fasulli', cancelActiveOrders: true);

    expect($cancelled)->toBe(1)
        ->and($order->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($product->fresh()->stock)->toBe(10)
        ->and($user->fresh()->blocked_reason)->toBe('ordini fasulli');
});

it('lascia stare gli ordini già evasi o annullati', function () {
    Mail::fake();
    [$user, $product, $order] = customerWithOrder('evaso', 2);

    expect($user->block(cancelActiveOrders: true))->toBe(0)
        ->and($order->fresh()->status)->toBe('evaso')
        ->and($product->fresh()->stock)->toBe(8);
});

it('blocca e sblocca dalla scheda utente', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    [$user, $product, $order] = customerWithOrder('nuovo', 4);

    Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->assertActionVisible('blockUser')
        ->assertActionHidden('unblockUser')
        ->callAction('blockUser', ['cancel_orders' => true, 'reason' => 'spam'])
        ->assertNotified('Account bloccato');

    expect($user->fresh()->isBlocked())->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::STATUS_CANCELLED)
        ->and($product->fresh()->stock)->toBe(10);

    Mail::assertQueued(OrderStatusUpdated::class);

    Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $user->getRouteKey()])
        ->assertActionHidden('blockUser')
        ->callAction('unblockUser')
        ->assertNotified('Account sbloccato');

    expect($user->fresh()->isBlocked())->toBeFalse();
});
