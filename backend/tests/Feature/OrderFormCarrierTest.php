<?php

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create(['is_admin' => true])));

it('propone Poste Italiane nei nuovi ordini', function () {
    Livewire::test(CreateOrder::class)->assertSchemaStateSet(['carrier' => 'Poste Italiane']);
});

it('propone Poste Italiane negli ordini arrivati dal sito senza corriere', function () {
    $order = Order::create(['customer_name' => 'Mario', 'customer_email' => 'mario@example.test', 'status' => 'nuovo', 'total' => 10]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSchemaStateSet(['carrier' => 'Poste Italiane']);
});

it('non cambia un corriere già scelto', function () {
    $order = Order::create(['customer_name' => 'Mario', 'customer_email' => 'mario@example.test', 'status' => 'nuovo', 'total' => 10, 'carrier' => 'BRT']);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->assertSchemaStateSet(['carrier' => 'BRT']);
});
