<?php

use App\Filament\Resources\Discounts\Pages\ListDiscounts;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\WelcomeCodes\Pages\ListWelcomeCodes;
use App\Mail\WelcomeDiscountMail;
use App\Models\Category;
use App\Models\Comune;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    $comune = Comune::forceCreate(['name' => 'Napoli', 'latitude' => 40.85, 'longitude' => 14.26]);
    $this->rate = ShippingRate::create(['name' => 'Campania', 'type' => 'regione', 'price' => 5, 'is_active' => true]);
    $this->product = Product::create([
        'category_id' => Category::create(['name' => 'Trucco'])->id,
        'name' => 'Rossetto', 'slug' => 'rossetto', 'price' => 20, 'stock' => 50, 'is_active' => true,
    ]);

    // Un cliente che ha appena verificato l'email: riceve il codice.
    $this->makeCustomer = function (string $email = 'anna@example.test') use ($comune) {
        $user = User::factory()->unverified()->create(['name' => 'Anna Rossi', 'email' => $email]);
        $user->addresses()->create([
            'recipient_name' => 'Anna Rossi', 'phone' => '3330000000', 'address_line' => 'Via Roma 1',
            'comune_id' => $comune->id, 'postal_code' => '80121', 'province' => 'NA', 'is_default' => true,
        ]);
        $user->markEmailAsVerified();

        return $user;
    };

    $this->order = fn (User $user, ?string $code, int $qty = 1) => $this->actingAs($user)->postJson('/api/orders', array_filter([
        'address_id' => $user->addresses()->first()->id,
        'shipping_rate_id' => $this->rate->id,
        'discount_code' => $code,
        'items' => [['product_id' => $this->product->id, 'quantity' => $qty]],
    ], fn ($v) => $v !== null));

    $this->anna = ($this->makeCustomer)();
    $this->code = Discount::welcome()->where('user_id', $this->anna->id)->firstOrFail();
});

it('creates a personal 15% code valid for 30 days and emails it when the email is verified', function () {
    expect($this->code->kind)->toBe('welcome')
        ->and($this->code->type)->toBe('percentuale')
        ->and((float) $this->code->value)->toBe(15.0)
        ->and($this->code->code)->toMatch('/^BENVENUTO-[A-HJ-NP-Z2-9]{6}$/')
        ->and($this->code->ends_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($this->code->emailed_at)->not->toBeNull();

    Mail::assertQueued(WelcomeDiscountMail::class, fn ($mail) => $mail->hasTo('anna@example.test'));

    $html = (new WelcomeDiscountMail($this->anna, $this->code))->render();
    expect($html)->toContain($this->code->code)->toContain('15%')->toContain('Anna')
        ->toContain($this->code->ends_at->locale('it')->translatedFormat('j F Y'));
});

it('works through the signed verification link and sends the code only once', function () {
    $user = User::factory()->unverified()->create(['email' => 'luca@example.test']);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->get($url)->assertRedirect();
    $this->get($url)->assertRedirect();

    expect(Discount::welcome()->where('user_id', $user->id)->count())->toBe(1);
    Mail::assertQueued(WelcomeDiscountMail::class, fn ($mail) => $mail->hasTo('luca@example.test'));
    Mail::assertQueuedCount(2);   // Anna (beforeEach) + Luca, una sola volta ciascuno
});

it('does not give a welcome code to staff accounts', function () {
    $admin = User::factory()->unverified()->create(['is_admin' => true]);
    $admin->markEmailAsVerified();

    expect(Discount::welcome()->where('user_id', $admin->id)->exists())->toBeFalse();
});

it('applies 15% on the products total (shipping excluded) and consumes the code', function () {
    $response = ($this->order)($this->anna, $this->code->code, 1)->assertCreated();

    expect((float) $response->json('total'))->toBe(22.0);   // 20 - 15% (3) + 5 di spedizione
    expect($this->code->fresh()->isUsed())->toBeTrue();
});

it('accepts the code typed in lowercase and with spaces', function () {
    ($this->order)($this->anna, '  '.mb_strtolower($this->code->code).' ')->assertCreated();
});

it('allows only one use per account', function () {
    ($this->order)($this->anna, $this->code->code)->assertCreated();

    ($this->order)($this->anna, $this->code->code)
        ->assertStatus(422)
        ->assertJsonPath('errors.discount_code.0', 'Hai già usato il tuo codice di benvenuto.');
});

it('does not work for another customer', function () {
    $luca = ($this->makeCustomer)('luca@example.test');

    ($this->order)($luca, $this->code->code)
        ->assertStatus(422)
        ->assertJsonPath('errors.discount_code.0', 'Codice sconto non valido o scaduto.');

    expect($this->code->fresh()->isUsed())->toBeFalse();
});

it('expires after 30 days', function () {
    $this->travel(31)->days();

    ($this->order)($this->anna, $this->code->code)
        ->assertStatus(422)
        ->assertJsonPath('errors.discount_code.0', 'Il tuo codice di benvenuto è scaduto.');
});

it('is still valid on the last day', function () {
    $this->travel(29)->days();

    ($this->order)($this->anna, $this->code->code)->assertCreated();
});

it('becomes available again if the order that used it is cancelled', function () {
    $orderId = ($this->order)($this->anna, $this->code->code)->assertCreated()->json('id');
    ($this->order)($this->anna, $this->code->code)->assertStatus(422);

    Order::findOrFail($orderId)->update(['status' => Order::STATUS_CANCELLED]);

    expect($this->code->fresh()->isUsed())->toBeFalse();
    ($this->order)($this->anna, $this->code->code)->assertCreated();
});

it('keeps regular codes working for everyone, any number of times', function () {
    $standard = Discount::create(['code' => 'ESTATE10', 'type' => 'percentuale', 'value' => 10, 'is_active' => true]);
    $luca = ($this->makeCustomer)('luca@example.test');

    foreach ([$this->anna, $luca, $this->anna] as $user) {
        expect((float) ($this->order)($user, 'estate10')->assertCreated()->json('total'))->toBe(23.0);   // 18 + 5
    }
    expect($standard->orders()->count())->toBe(3);
});

it('checks a code before the order without using it', function () {
    $this->actingAs($this->anna)->postJson('/api/discounts/check', ['code' => $this->code->code])
        ->assertOk()
        ->assertJsonPath('data.type', 'percentuale')
        ->assertJsonPath('data.value', 15);

    expect($this->code->fresh()->isUsed())->toBeFalse();

    $this->actingAs($this->anna)->postJson('/api/discounts/check', ['code' => 'NONESISTE'])
        ->assertStatus(422)->assertJsonPath('errors.code.0', 'Codice sconto non valido o scaduto.');

    $luca = ($this->makeCustomer)('luca@example.test');
    $this->actingAs($luca)->postJson('/api/discounts/check', ['code' => $this->code->code])->assertStatus(422);

    $this->app['auth']->forgetGuards();
    $this->postJson('/api/discounts/check', ['code' => $this->code->code])->assertUnauthorized();
});

it('shows the admin who received, used or is about to lose the code', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $luca = ($this->makeCustomer)('luca@example.test');                       // giorno 0: codice fino al giorno 30

    ($this->order)($this->anna, $this->code->code)->assertCreated();          // Anna lo usa subito

    $this->travel(24)->days();                                                // giorno 24
    ($this->makeCustomer)('marco@example.test');                              // Marco: codice fino al giorno 54

    $this->actingAs($admin);
    $tabs = fn () => collect((new ListWelcomeCodes)->getTabs())->map(fn ($tab) => $tab->getBadge())->all();
    $status = fn (User $user) => \App\Filament\Resources\WelcomeCodes\WelcomeCodeResource::status(
        \App\Filament\Resources\WelcomeCodes\WelcomeCodeResource::getEloquentQuery()->where('user_id', $user->id)->first()
    );

    // Giorno 24: Luca ha 6 giorni (in scadenza), Marco 30 (da usare), Anna ha usato.
    expect($tabs())->toBe(['tutti' => '3', 'da-usare' => '2', 'in-scadenza' => '1', 'usati' => '1', 'scaduti' => '0'])
        ->and($status($this->anna))->toBe('Usato')
        ->and($status($luca))->toBe('In scadenza');

    // Giorno 31: Luca non l'ha usato ed è scaduto.
    $this->travel(7)->days();

    expect($tabs())->toBe(['tutti' => '3', 'da-usare' => '1', 'in-scadenza' => '0', 'usati' => '1', 'scaduti' => '1'])
        ->and($status($luca))->toBe('Scaduto');
});

it('lists welcome codes by tab for the admin', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $luca = ($this->makeCustomer)('luca@example.test');
    $lucaCode = Discount::welcome()->where('user_id', $luca->id)->first();
    ($this->order)($luca, $lucaCode->code)->assertCreated();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ListWelcomeCodes::class)
        ->assertCanSeeTableRecords(Discount::welcome()->get())
        ->set('activeTab', 'usati')->assertCanSeeTableRecords([$lucaCode])->assertCanNotSeeTableRecords([$this->code])
        ->set('activeTab', 'da-usare')->assertCanSeeTableRecords([$this->code])->assertCanNotSeeTableRecords([$lucaCode]);
});

it('keeps personal codes out of the regular discounts list', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $standard = Discount::create(['code' => 'ESTATE10', 'type' => 'percentuale', 'value' => 10, 'is_active' => true]);

    Livewire::test(ListDiscounts::class)
        ->assertCanSeeTableRecords([$standard])
        ->assertCanNotSeeTableRecords([$this->code]);
});

it('does not lose the welcome code when the admin edits and saves the order', function () {
    Repeater::fake();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $orderId = ($this->order)($this->anna, $this->code->code)->assertCreated()->json('id');
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(EditOrder::class, ['record' => $orderId])
        ->fillForm(['notes' => 'Nota interna'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Order::find($orderId)->discount_id)->toBe($this->code->id);
});

it('rounds the discount to the cent so the final total matches the checkout preview', function () {
    $this->product->update(['price' => 8.90]);

    // 15% di 8,90 = 1,335 → 1,34 (come nel riepilogo del negozio); totale 8,90 − 1,34 + 5 di spedizione.
    $total = ($this->order)($this->anna, $this->code->code)->assertCreated()->json('total');

    expect((float) $total)->toBe(12.56);
});
