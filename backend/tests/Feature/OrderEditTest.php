<?php

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Mail\OrderChangedForAdmin;
use App\Mail\OrderStatusUpdated;
use App\Mail\OrderUpdatedMail;
use App\Models\Category;
use App\Models\Comune;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['app.admin_order_email' => 'admin@example.test']);

    $this->admin = User::factory()->create(['is_admin' => true]);

    $this->napoli = Comune::forceCreate(['name' => 'Napoli', 'latitude' => 40.85, 'longitude' => 14.26]);
    $this->rate = ShippingRate::create(['name' => 'Campania', 'type' => 'regione', 'price' => 5, 'is_active' => true]);
    $this->rateSicilia = ShippingRate::create(['name' => 'Sicilia', 'type' => 'regione', 'price' => 9.5, 'is_active' => true]);

    $category = Category::create(['name' => 'Trucco']);
    $this->gloss = Product::create(['category_id' => $category->id, 'name' => 'Gloss', 'slug' => 'gloss', 'price' => 10, 'stock' => 20, 'is_active' => true]);
    $this->mascara = Product::create(['category_id' => $category->id, 'name' => 'Mascara', 'slug' => 'mascara', 'price' => 6, 'stock' => 3, 'is_active' => true]);

    $this->anna = User::factory()->create(['name' => 'Anna Rossi', 'email' => 'anna@example.test']);
    $this->home = $this->anna->addresses()->create([
        'recipient_name' => 'Anna Rossi', 'phone' => '3330000000', 'address_line' => 'Via Roma 1',
        'comune_id' => $this->napoli->id, 'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
    ]);
    $this->work = $this->anna->addresses()->create([
        'recipient_name' => 'Anna Rossi', 'phone' => '3330000000', 'address_line' => 'Via Etnea 5',
        'comune_id' => Comune::forceCreate(['name' => 'Catania', 'latitude' => 37.5, 'longitude' => 15.08])->id,
        'postal_code' => '95100', 'province' => 'CT',
    ]);

    // Ordine fatto dal cliente: 2 gloss (10 € l'uno) + spedizione 5 € = 25 €.
    $this->placed = fn (?string $code = null) => $this->actingAs($this->anna)->postJson('/api/orders', array_filter([
        'address_id' => $this->home->id,
        'shipping_rate_id' => $this->rate->id,
        'discount_code' => $code,
        'items' => [['product_id' => $this->gloss->id, 'quantity' => 2]],
    ]))->assertCreated()->json('id');

    $this->edit = fn (int $orderId, array $items, ?int $addressId = null, ?int $rateId = null, ?User $as = null) => $this->actingAs($as ?? $this->anna)
        ->patchJson("/api/orders/{$orderId}", [
            'address_id' => $addressId ?? $this->home->id,
            'shipping_rate_id' => $rateId ?? $this->rate->id,
            'items' => $items,
        ]);

    $this->orderId = ($this->placed)();
    Mail::fake();   // da qui contano solo le email della modifica
});

it('lets the customer add a product, change a quantity and remove nothing: totals and stock follow', function () {
    $response = ($this->edit)($this->orderId, [
        ['product_id' => $this->gloss->id, 'quantity' => 3],
        ['product_id' => $this->mascara->id, 'quantity' => 1],
    ])->assertOk();

    expect((float) $response->json('total'))->toBe(41.0)            // 30 + 6 + 5
        ->and($this->gloss->fresh()->stock)->toBe(17)               // 20 - 3
        ->and($this->mascara->fresh()->stock)->toBe(2);             // 3 - 1
});

it('removes a product and gives the pieces back', function () {
    ($this->edit)($this->orderId, [['product_id' => $this->mascara->id, 'quantity' => 2]])->assertOk();

    expect($this->gloss->fresh()->stock)->toBe(20)                  // le 2 tornate
        ->and($this->mascara->fresh()->stock)->toBe(1)
        ->and(Order::find($this->orderId)->items()->count())->toBe(1);
});

it('keeps the original price of existing lines and uses today\'s price for new ones', function () {
    $this->gloss->update(['price' => 12]);   // dopo l'ordine il prezzo sale

    $response = ($this->edit)($this->orderId, [
        ['product_id' => $this->gloss->id, 'quantity' => 2],
        ['product_id' => $this->mascara->id, 'quantity' => 1],
    ])->assertOk();

    expect((float) $response->json('total'))->toBe(31.0);           // 2×10 (vecchio prezzo) + 6 + 5
});

it('refuses more pieces than available, counting the ones already held by the order', function () {
    // Gloss: 18 in magazzino + 2 già nell'ordine = al massimo 20.
    ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 20]])->assertOk();
    ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 21]])
        ->assertStatus(422)->assertJsonValidationErrors('items');

    ($this->edit)($this->orderId, [['product_id' => $this->mascara->id, 'quantity' => 4]])->assertStatus(422);

    expect($this->gloss->fresh()->stock)->toBe(0);                  // nessun pezzo perso o duplicato
});

it('needs at least one product', function () {
    $this->actingAs($this->anna)->patchJson("/api/orders/{$this->orderId}", [
        'address_id' => $this->home->id, 'shipping_rate_id' => $this->rate->id, 'items' => [],
    ])->assertStatus(422);
});

it('changes address and shipping, recomputing the total', function () {
    $response = ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 2]], $this->work->id, $this->rateSicilia->id)->assertOk();

    $order = Order::find($this->orderId);
    expect((float) $response->json('total'))->toBe(29.5)            // 20 + 9,50
        ->and($order->shipping_address_line)->toBe('Via Etnea 5')
        ->and($order->shipping_city)->toBe('Catania')
        ->and($order->shipping_province)->toBe('CT')
        ->and($order->shipping_rate_id)->toBe($this->rateSicilia->id);
});

it('cannot use someone else\'s address', function () {
    $luca = User::factory()->create();
    $lucaHome = $luca->addresses()->create([
        'recipient_name' => 'Luca', 'phone' => '3330000001', 'address_line' => 'Via Dante 2',
        'comune_id' => $this->napoli->id, 'postal_code' => '80121', 'province' => 'NA',
    ]);

    ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 2]], $lucaHome->id)->assertForbidden();
});

it('keeps the discount code and recomputes it on the new products', function () {
    $code = Discount::create(['code' => 'ESTATE10', 'type' => 'percentuale', 'value' => 10, 'is_active' => true]);
    $orderId = ($this->placed)('ESTATE10');                         // 20 - 2 + 5 = 23

    $response = ($this->edit)($orderId, [['product_id' => $this->gloss->id, 'quantity' => 4]])->assertOk();

    expect((float) $response->json('total'))->toBe(41.0)            // 40 - 4 + 5
        ->and(Order::find($orderId)->discount_id)->toBe($code->id);
});

it('does not let another customer touch the order', function () {
    $luca = User::factory()->create();

    ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 1]], null, null, $luca)->assertForbidden();
    $this->actingAs($luca)->postJson("/api/orders/{$this->orderId}/cancel")->assertForbidden();
});

it('locks the order once it is paid (in lavorazione), shipped or cancelled', function () {
    foreach (['in_lavorazione', 'evaso', 'annullato'] as $status) {
        Order::find($this->orderId)->update(['status' => $status]);

        ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 1]])
            ->assertStatus(422)->assertJsonPath('errors.order.0', \App\Support\OrderEditor::LOCKED_MESSAGE);
        $this->actingAs($this->anna)->postJson("/api/orders/{$this->orderId}/cancel")->assertStatus(422);
    }
});

it('emails the customer and the admin, records the change and notifies both bells', function () {
    ($this->edit)($this->orderId, [
        ['product_id' => $this->gloss->id, 'quantity' => 3],
        ['product_id' => $this->mascara->id, 'quantity' => 1],
    ])->assertOk();

    Mail::assertQueued(OrderUpdatedMail::class, fn ($m) => $m->hasTo('anna@example.test') && $m->byCustomer
        && in_array('Aggiunto: Mascara × 1', $m->lines) && in_array('Gloss: quantità da 2 a 3', $m->lines));
    Mail::assertQueued(OrderChangedForAdmin::class, fn ($m) => $m->hasTo('admin@example.test') && $m->type === OrderEvent::TYPE_EDITED);

    $event = Order::find($this->orderId)->events()->first();
    expect($event->actor)->toBe('customer')
        ->and($event->summary)->toContain('Totale: 25,00 € → 41,00 €')
        ->and($event->notified_at)->not->toBeNull();

    $titles = fn (User $user) => $user->notifications->pluck('data.title')->implode(' | ');
    expect($titles($this->anna))->toContain('Hai modificato l\'ordine #'.str_pad((string) $this->orderId, 5, '0', STR_PAD_LEFT))
        ->and($titles($this->admin))->toContain('modificato dal cliente');
});

it('does not email anyone when nothing really changed', function () {
    ($this->edit)($this->orderId, [['product_id' => $this->gloss->id, 'quantity' => 2]])->assertOk();

    Mail::assertNothingQueued();
    expect(Order::find($this->orderId)->events()->count())->toBe(0);
});

it('cancels the order: stock returns, welcome code is freed, everybody is told', function () {
    // Codice di benvenuto usato da quest'ordine.
    $anna = User::factory()->unverified()->create(['email' => 'nuova@example.test']);
    $anna->addresses()->create([
        'recipient_name' => 'Nuova', 'phone' => '3330000002', 'address_line' => 'Via Mare 3',
        'comune_id' => $this->napoli->id, 'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
    ]);
    $anna->markEmailAsVerified();
    $welcome = Discount::welcome()->where('user_id', $anna->id)->firstOrFail();

    $orderId = $this->actingAs($anna)->postJson('/api/orders', [
        'address_id' => $anna->addresses()->first()->id, 'shipping_rate_id' => $this->rate->id, 'discount_code' => $welcome->code,
        'items' => [['product_id' => $this->mascara->id, 'quantity' => 2]],
    ])->assertCreated()->json('id');
    expect($welcome->fresh()->isUsed())->toBeTrue();
    Mail::fake();

    $this->actingAs($anna)->postJson("/api/orders/{$orderId}/cancel")->assertOk()->assertJsonPath('status', 'annullato');

    expect($this->mascara->fresh()->stock)->toBe(3)                 // le 2 tornate
        ->and($welcome->fresh()->isUsed())->toBeFalse()
        ->and(Order::find($orderId)->events()->first()->type)->toBe('cancelled');

    Mail::assertQueued(OrderStatusUpdated::class, fn ($m) => $m->hasTo('nuova@example.test') && $m->status === 'annullato');
    Mail::assertQueued(OrderChangedForAdmin::class, fn ($m) => $m->hasTo('admin@example.test') && $m->type === OrderEvent::TYPE_CANCELLED);
    expect($this->admin->notifications->pluck('data.title')->implode(' | '))->toContain('annullato dal cliente');
});

it('records admin edits as pending and lets the admin notify the customer in one go', function () {
    Repeater::fake();
    $this->actingAs($this->admin);

    $page = Livewire::test(EditOrder::class, ['record' => $this->orderId])
        ->assertActionDisabled('notifyCustomer');                   // niente da comunicare

    $page->fillForm(['shipping_cost' => 8])->call('save')->assertHasNoFormErrors();
    $page->fillForm(['shipping_cost' => 9])->call('save')->assertHasNoFormErrors();

    $pending = Order::find($this->orderId)->events()->whereNull('notified_at')->get();
    expect($pending)->toHaveCount(2)
        ->and($pending->first()->actor)->toBe('admin');

    Mail::assertNothingQueued();                                    // il cliente NON è avvisato da solo

    $page->assertActionEnabled('notifyCustomer')->callAction('notifyCustomer');

    Mail::assertQueued(OrderUpdatedMail::class, fn ($m) => $m->hasTo('anna@example.test') && ! $m->byCustomer
        && collect($m->lines)->contains(fn ($l) => str_contains($l, 'Totale:')));
    Mail::assertQueuedCount(1);                                     // due modifiche, una sola email
    expect(Order::find($this->orderId)->events()->whereNull('notified_at')->count())->toBe(0)
        ->and($this->anna->notifications->pluck('data.title')->implode(' | '))->toContain('è stato aggiornato');
});
