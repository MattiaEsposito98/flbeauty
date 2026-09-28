<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Widgets\CookieBannerStats;
use App\Filament\Resources\Users\Widgets\TopCitiesChart;
use App\Filament\Resources\Users\Widgets\UserActivityChart;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use App\Models\CookieConsentStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registra gli accessi riusciti dal sito', function () {
    $user = User::factory()->create(['username' => 'mario', 'password' => 'Password123']);

    $this->postJson('/api/login', ['login' => 'mario', 'password' => 'Password123'])->assertOk();
    $this->postJson('/api/login', ['login' => 'mario', 'password' => 'sbagliata'])->assertStatus(422);

    $user->refresh();
    expect($user->login_count)->toBe(1)
        ->and($user->last_login_at)->not->toBeNull()
        ->and($user->logins()->count())->toBe(1);
});

it('conta in forma anonima le scelte sul banner cookie', function () {
    $this->postJson('/api/cookie-consent-stats', ['event' => 'shown'])->assertNoContent();
    $this->postJson('/api/cookie-consent-stats', ['event' => 'shown'])->assertNoContent();
    $this->postJson('/api/cookie-consent-stats', ['event' => 'accepted'])->assertNoContent();
    $this->postJson('/api/cookie-consent-stats', ['event' => 'altro'])->assertStatus(422);

    $today = CookieConsentStat::sole();
    expect($today->shown)->toBe(2)
        ->and($today->accepted)->toBe(1)
        ->and($today->rejected)->toBe(0);
});

it('mostra agli admin gli utenti in ordine alfabetico, senza lo staff', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin']);
    $zeta = User::factory()->create(['name' => 'Zeta Rossi']);
    $anna = User::factory()->create(['name' => 'Anna Bianchi']);

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$anna, $zeta], inOrder: true)
        ->assertCanNotSeeTableRecords([$admin]);

    Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $anna->getRouteKey()])
        ->assertOk()
        ->assertSee('Anna Bianchi');
});

it('carica tutti i widget delle statistiche', function (string $widget) {
    User::factory()->create(['name' => 'Cliente']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test($widget)->assertOk();
})->with([
    UsersOverview::class,
    UserActivityChart::class,
    TopCitiesChart::class,
    CookieBannerStats::class,
]);
