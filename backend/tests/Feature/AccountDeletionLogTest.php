<?php

use App\Filament\Resources\AccountDeletions\Pages\ListAccountDeletions;
use App\Models\AccountDeletion;
use App\Models\Discount;
use App\Models\User;
use App\Support\WelcomeDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function deleteOwnAccount(User $user): void
{
    Sanctum::actingAs($user);
    test()->deleteJson('/api/user', ['password' => 'Password123'])->assertOk();
}

it('registra chi si elimina l\'account, con email in minuscolo e se aveva il codice di benvenuto', function () {
    Mail::fake();
    $user = User::factory()->create(['name' => 'Giulia Rossi', 'email' => 'Giulia.Rossi@Example.test', 'password' => 'Password123']);
    WelcomeDiscount::issueFor($user);

    deleteOwnAccount($user);

    $log = AccountDeletion::sole();
    expect($log->name)->toBe('Giulia Rossi')
        ->and($log->email)->toBe('giulia.rossi@example.test')
        ->and($log->welcome_code_issued)->toBeTrue()
        ->and($log->registered_at)->not->toBeNull()
        ->and(User::find($user->id))->toBeNull();
});

it('non ridà il codice di benvenuto a chi si riregistra con la stessa email dopo averlo ricevuto', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'anna@example.test', 'password' => 'Password123']);
    WelcomeDiscount::issueFor($user);
    expect(Discount::welcome()->count())->toBe(1);

    deleteOwnAccount($user);

    $again = User::factory()->unverified()->create(['email' => 'ANNA@example.test']);
    $again->markEmailAsVerified();

    expect(Discount::welcome()->where('user_id', $again->id)->exists())->toBeFalse()
        ->and(Discount::welcome()->count())->toBe(1);   // resta solo il vecchio codice, senza cliente
    // Resta solo l'email di benvenuto del primo account: la seconda registrazione non ne manda altre.
    Mail::assertQueued(\App\Mail\WelcomeDiscountMail::class, 1);
});

it('dà il codice a chi si era eliminato senza averlo mai ricevuto (email mai verificata)', function () {
    Mail::fake();
    $user = User::factory()->unverified()->create(['email' => 'luca@example.test', 'password' => 'Password123']);

    deleteOwnAccount($user);
    expect(AccountDeletion::sole()->welcome_code_issued)->toBeFalse();

    $again = User::factory()->unverified()->create(['email' => 'luca@example.test']);
    $again->markEmailAsVerified();

    expect(Discount::welcome()->where('user_id', $again->id)->exists())->toBeTrue();
});

it('mostra il registro in admin, nella scheda Eliminati di Utenti', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $log = AccountDeletion::create(['name' => 'Marta Bianchi', 'email' => 'marta@example.test', 'welcome_code_issued' => true]);

    $this->get('/admin/users')->assertOk()->assertSee('Eliminati');
    $this->get('/admin/account-eliminati')->assertOk()->assertSee('Marta Bianchi');

    Livewire::test(ListAccountDeletions::class)->assertCanSeeTableRecords([$log]);
});

it('svuota dal registro le righe più vecchie del limite', function () {
    $old = AccountDeletion::create(['name' => 'Vecchio', 'email' => 'vecchio@example.test']);
    $old->forceFill(['created_at' => now()->subMonths(config('shop.deletion_log_months'))->subDay()])->save();
    $recent = AccountDeletion::create(['name' => 'Recente', 'email' => 'recente@example.test']);

    expect(AccountDeletion::prune())->toBe(1)
        ->and(AccountDeletion::find($old->id))->toBeNull()
        ->and(AccountDeletion::find($recent->id))->not->toBeNull();
});
