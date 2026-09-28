<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function loginToken(User $user, string $password = 'Password123'): string
{
    return test()->postJson('/api/login', ['login' => $user->username, 'password' => $password])
        ->assertOk()
        ->json('token');
}

it('cambia la password e chiude gli accessi sugli altri dispositivi', function () {
    $user = User::factory()->create(['username' => 'mario', 'password' => 'Password123']);
    loginToken($user);
    $current = loginToken($user);

    $this->withToken($current)
        ->putJson('/api/user/password', [
            'current_password' => 'sbagliata',
            'password' => 'NuovaPass456',
            'password_confirmation' => 'NuovaPass456',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.current_password.0', 'La password attuale non è corretta.');

    $this->withToken($current)
        ->putJson('/api/user/password', [
            'current_password' => 'Password123',
            'password' => 'NuovaPass456',
            'password_confirmation' => 'NuovaPass456',
        ])
        ->assertOk();

    expect(Hash::check('NuovaPass456', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(1);
});

it('elimina l\'account con la password giusta e tiene gli ordini', function () {
    $user = User::factory()->create(['username' => 'mario', 'password' => 'Password123']);
    $order = Order::create([
        'user_id' => $user->id,
        'customer_name' => 'Mario',
        'customer_email' => $user->email,
        'status' => 'evaso',
        'total' => 10,
    ]);
    $token = loginToken($user);

    $this->withToken($token)->deleteJson('/api/user', ['password' => 'sbagliata'])->assertStatus(422);
    expect(User::find($user->id))->not->toBeNull();

    $this->withToken($token)->deleteJson('/api/user', ['password' => 'Password123'])->assertOk();

    expect(User::find($user->id))->toBeNull()
        ->and($order->fresh())->not->toBeNull()
        ->and($order->fresh()->user_id)->toBeNull();
});

it('non elimina gli account dello staff dal sito', function () {
    $admin = User::factory()->create(['username' => 'staff', 'password' => 'Password123', 'is_admin' => true]);

    $this->withToken(loginToken($admin))->deleteJson('/api/user', ['password' => 'Password123'])->assertStatus(422);

    expect(User::find($admin->id))->not->toBeNull();
});
