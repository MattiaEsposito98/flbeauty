<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('crea un amministratore con password nascosta e email già verificata', function () {
    $this->artisan('admin:create', ['--name' => 'Flavia Esposito', '--username' => 'flavia', '--email' => 'info@flbeauty.it'])
        ->expectsQuestion('Password (almeno 12 caratteri, non si vede mentre scrivi)', 'una-password-lunga-1')
        ->expectsQuestion('Ripeti la password', 'una-password-lunga-1')
        ->assertSuccessful();

    $admin = User::where('email', 'info@flbeauty.it')->firstOrFail();

    expect($admin->is_admin)->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->password)->not->toBe('una-password-lunga-1')
        ->and($admin->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeTrue();
});

it('rifiuta password corte o diverse e email già usate', function () {
    $this->artisan('admin:create', ['--name' => 'A', '--username' => 'a', '--email' => 'a@example.test'])
        ->expectsQuestion('Password (almeno 12 caratteri, non si vede mentre scrivi)', 'corta')
        ->expectsQuestion('Ripeti la password', 'corta')
        ->assertFailed();

    $this->artisan('admin:create', ['--name' => 'A', '--username' => 'a', '--email' => 'a@example.test'])
        ->expectsQuestion('Password (almeno 12 caratteri, non si vede mentre scrivi)', 'una-password-lunga-1')
        ->expectsQuestion('Ripeti la password', 'una-password-lunga-2')
        ->assertFailed();

    expect(User::count())->toBe(0);

    User::factory()->create(['email' => 'occupata@example.test']);

    $this->artisan('admin:create', ['--name' => 'B', '--username' => 'b', '--email' => 'occupata@example.test'])
        ->assertFailed();
});
