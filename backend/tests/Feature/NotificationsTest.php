<?php

use App\Filament\Resources\Communications\Pages\CreateCommunication;
use App\Models\Category;
use App\Models\Comune;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use App\Support\SiteNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['app.admin_order_email' => 'admin@example.test']);

    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->anna = User::factory()->create(['name' => 'Anna Rossi', 'email' => 'anna@example.test', 'marketing_consent' => false]);
    $this->luca = User::factory()->create(['name' => 'Luca Bianchi', 'email' => 'luca@example.test', 'marketing_consent' => true]);

    $comune = Comune::forceCreate(['name' => 'Napoli', 'latitude' => 40.85, 'longitude' => 14.26]);
    $this->address = $this->anna->addresses()->create([
        'recipient_name' => 'Anna Rossi', 'phone' => '3330000000', 'address_line' => 'Via Roma 1',
        'comune_id' => $comune->id, 'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
    ]);
    $this->rate = ShippingRate::create(['name' => 'Campania', 'type' => 'regione', 'price' => 5, 'is_active' => true]);
    $this->product = Product::create([
        'category_id' => Category::create(['name' => 'Trucco'])->id,
        'name' => 'Gloss', 'slug' => 'gloss', 'price' => 10, 'stock' => 20, 'is_active' => true,
    ]);

    $this->titles = fn (User $user) => $user->notifications()->latest()->orderByDesc('id')->get()->pluck('data.title')->all();
});

it('lists the customer\'s notifications newest first, with the unread count', function () {
    SiteNotifier::toUser($this->anna, 'order', 'Prima', 'testo', '/ordini/1');
    SiteNotifier::toUser($this->anna, 'welcome', 'Seconda');
    SiteNotifier::toUser($this->luca, 'order', 'Di Luca');

    $response = $this->actingAs($this->anna)->getJson('/api/notifications')->assertOk();

    expect($response->json('unread_count'))->toBe(2)
        ->and(collect($response->json('data'))->pluck('title')->all())->toBe(['Seconda', 'Prima'])
        ->and($response->json('data.1'))->toMatchArray(['kind' => 'order', 'body' => 'testo', 'url' => '/ordini/1', 'read' => false]);

    $this->actingAs($this->anna)->getJson('/api/notifications/unread-count')->assertOk()->assertJson(['unread_count' => 2]);
});

it('marks one or all notifications as read, only for their owner', function () {
    SiteNotifier::toUser($this->anna, 'order', 'Uno');
    SiteNotifier::toUser($this->anna, 'order', 'Due');
    $first = $this->anna->notifications()->orderBy('id')->first();
    $lucas = SiteNotifier::toUser($this->luca, 'order', 'Di Luca');
    $lucaNotification = $this->luca->notifications()->first();

    // Luca non può segnare come letta quella di Anna.
    $this->actingAs($this->luca)->postJson("/api/notifications/{$first->id}/read")->assertNoContent();
    expect($first->fresh()->read_at)->toBeNull();

    $this->actingAs($this->anna)->postJson("/api/notifications/{$first->id}/read")->assertNoContent();
    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($this->anna->unreadNotifications()->count())->toBe(1);

    $this->actingAs($this->anna)->postJson('/api/notifications/read-all')->assertNoContent();
    expect($this->anna->unreadNotifications()->count())->toBe(0)
        ->and($lucaNotification->fresh()->read_at)->toBeNull();
});

it('needs a login', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();
});

it('never creates customer notifications for staff', function () {
    SiteNotifier::toUser($this->admin, 'order', 'Non deve esistere');

    expect($this->admin->notifications()->count())->toBe(0);
});

it('mirrors the order emails: placed, and every status change', function () {
    $orderId = $this->actingAs($this->anna)->postJson('/api/orders', [
        'address_id' => $this->address->id, 'shipping_rate_id' => $this->rate->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
    ])->assertCreated()->json('id');
    $number = '#'.str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);

    expect(($this->titles)($this->anna))->toBe(["Ordine {$number} ricevuto"])
        ->and(implode('|', ($this->titles)($this->admin)))->toContain("Nuovo ordine {$number}");

    $order = Order::find($orderId);
    $order->update(['status' => 'in_lavorazione']);
    $order->update(['status' => 'evaso', 'carrier' => 'SDA', 'tracking_number' => 'AB123']);

    $notifications = $this->anna->notifications()->get();
    expect($notifications->pluck('data.title')->all())->toContain("Ordine {$number}: In lavorazione", "Ordine {$number}: Evaso")
        ->and($notifications->firstWhere('data.title', "Ordine {$number}: Evaso")->data['body'])->toContain('AB123')
        ->and($notifications->first()->data['url'])->toBe("/ordini/{$orderId}");
});

it('does not create customer notifications for guest orders (email only)', function () {
    $order = Order::factory()->create(['user_id' => null, 'customer_email' => 'ospite@example.test', 'status' => 'nuovo']);
    $order->update(['status' => 'evaso']);

    expect(\Illuminate\Notifications\DatabaseNotification::where('notifiable_type', User::class)->whereNot('notifiable_id', $this->admin->id)->count())->toBe(0);
});

it('shows promotional communications to every customer, even without email consent', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateCommunication::class)
        ->fillForm(['type' => 'marketing', 'subject' => 'Saldi di stagione', 'body' => 'Sconti fino al 30% su tutto il sito.'])
        ->call('create')
        ->assertHasNoFormErrors();

    // Email: solo chi ha dato il consenso (Luca). Campanella: tutti i clienti (Anna e Luca), mai lo staff.
    Mail::assertQueuedCount(1);
    expect(($this->titles)($this->anna))->toBe(['Saldi di stagione'])
        ->and(($this->titles)($this->luca))->toBe(['Saldi di stagione'])
        ->and($this->anna->notifications()->first()->data['kind'])->toBe('promo')
        ->and($this->anna->notifications()->first()->data['body'])->toBe('Sconti fino al 30% su tutto il sito.')
        ->and(collect(($this->titles)($this->admin)))->not->toContain('Saldi di stagione');
});

it('shows service communications only to the chosen customers', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateCommunication::class)
        ->fillForm(['type' => 'servizio', 'audience' => 'selected', 'user_ids' => [$this->luca->id], 'subject' => 'Ritardo spedizioni', 'body' => 'Per il maltempo le consegne tardano.'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(($this->titles)($this->luca))->toBe(['Ritardo spedizioni'])
        ->and(($this->titles)($this->anna))->toBe([])
        ->and($this->luca->notifications()->first()->data['kind'])->toBe('service');
});

it('prunes notifications older than the retention period', function () {
    SiteNotifier::toUser($this->anna, 'order', 'Vecchia');
    $this->travel(61)->days();
    SiteNotifier::toUser($this->anna, 'order', 'Recente');
    $this->travelBack();
    $this->travel(61)->days();

    $this->artisan('notifications:prune')->expectsOutputToContain('1 notifiche cancellate')->assertSuccessful();

    expect(($this->titles)($this->anna))->toBe(['Recente']);
});
