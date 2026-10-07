<?php

use App\Filament\Resources\Communications\Pages\CreateCommunication;
use App\Http\Controllers\Api\MarketingConsentController;
use App\Mail\BroadcastCommunication;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function customer(array $attributes = [], bool $marketing = false): User
{
    $user = User::factory()->create($attributes);
    $user->setMarketingConsent($marketing);

    return $user;
}

it('manda le promozionali solo a chi ha dato il consenso', function () {
    $yes = customer(['email' => 'si@example.test'], marketing: true);
    customer(['email' => 'no@example.test']);
    User::factory()->create(['email' => 'admin@example.test', 'is_admin' => true]);

    expect(Communication::recipients(Communication::TYPE_MARKETING)->pluck('email')->all())
        ->toBe(['si@example.test'])
        ->and(Communication::recipients(Communication::TYPE_SERVICE)->pluck('email')->sort()->values()->all())
        ->toBe(['no@example.test', 'si@example.test']);
});

it('invia dal pannello con il link di disiscrizione solo nelle promozionali', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    customer(['email' => 'si@example.test'], marketing: true);
    customer(['email' => 'no@example.test']);

    Livewire::actingAs($admin)
        ->test(CreateCommunication::class)
        ->fillForm(['type' => 'marketing', 'subject' => 'Offerta', 'body' => '<p>Sconto!</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertQueued(BroadcastCommunication::class, 1);
    Mail::assertQueued(BroadcastCommunication::class, fn ($mail) => $mail->hasTo('si@example.test')
        && str_contains($mail->unsubscribeUrls['page'], '/disiscrizione?u='));

    Livewire::actingAs($admin)
        ->test(CreateCommunication::class)
        ->fillForm(['type' => 'servizio', 'subject' => 'Avviso', 'body' => '<p>Chiusi per ferie</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertQueued(BroadcastCommunication::class, fn ($mail) => $mail->hasTo('no@example.test')
        && $mail->unsubscribeUrls === null);
});

it('invia un avviso di servizio solo ai clienti scelti', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $chosen = customer(['email' => 'scelto@example.test']);
    customer(['email' => 'altro@example.test']);

    Livewire::actingAs($admin)
        ->test(CreateCommunication::class)
        ->fillForm([
            'type' => 'servizio',
            'audience' => 'selected',
            'user_ids' => [$chosen->id],
            'subject' => 'Il tuo ordine',
            'body' => '<p>Ti scriviamo per il tuo ordine</p>',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertQueued(BroadcastCommunication::class, 1);
    Mail::assertQueued(BroadcastCommunication::class, fn ($mail) => $mail->hasTo('scelto@example.test')
        && $mail->unsubscribeUrls === null);

    $communication = Communication::latest('id')->first();
    expect($communication->audience)->toBe('selected')
        ->and($communication->recipients_count)->toBe(1);
});

it('richiede di scegliere almeno un cliente e ignora la selezione per le promozionali', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $chosen = customer(['email' => 'scelto@example.test']);
    customer(['email' => 'si@example.test'], marketing: true);

    Livewire::actingAs($admin)
        ->test(CreateCommunication::class)
        ->fillForm(['type' => 'servizio', 'audience' => 'selected', 'subject' => 'A', 'body' => '<p>B</p>'])
        ->call('create')
        ->assertHasFormErrors(['user_ids' => 'required']);

    Mail::assertNothingQueued();

    // Con una promozionale la selezione manuale non vale: restano solo i consensi.
    expect(Communication::recipients(Communication::TYPE_MARKETING, [$chosen->id])->pluck('email')->all())
        ->toBe(['si@example.test']);
});

it('disiscrive con il link firmato e rifiuta una firma falsa', function () {
    $user = customer(marketing: true);
    $apiUrl = MarketingConsentController::unsubscribeUrls($user)['api'];

    $this->postJson(str_replace('signature=', 'signature=x', $apiUrl))->assertForbidden();
    expect($user->fresh()->marketing_consent)->toBeTrue();

    $this->postJson($apiUrl)->assertOk();
    expect($user->fresh()->marketing_consent)->toBeFalse();
});

it('permette di cambiare il consenso dal profilo', function () {
    $user = customer();

    $this->actingAs($user)
        ->patchJson('/api/user/marketing-consent', ['marketing_consent' => true])
        ->assertOk()
        ->assertJsonPath('marketing_consent', true);

    expect($user->fresh()->marketing_consent_at)->not->toBeNull();
});
