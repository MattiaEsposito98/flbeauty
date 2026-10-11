<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\UserStats;
use App\Filament\Resources\Users\Widgets\CookieBannerStats;
use App\Filament\Resources\Users\Widgets\TopCitiesChart;
use App\Filament\Resources\Users\Widgets\UserActivityChart;
use App\Filament\Resources\Users\Widgets\UsersOverview;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->buyer = User::factory()->create();
    Order::factory()->create(['user_id' => $this->buyer->id, 'status' => 'nuovo']);
    $this->unverified = User::factory()->create(['email_verified_at' => null]);
    $this->blocked = User::factory()->create(['blocked_at' => now()]);
});

it('opens the customers page with the table first and the statistics widgets on their own page', function () {
    $this->get('/admin/users')->assertOk()
        ->assertSee('Clienti')->assertSee('Statistiche')
        ->assertDontSeeLivewire(UsersOverview::class)
        ->assertDontSeeLivewire(UserActivityChart::class)
        ->assertDontSeeLivewire(CookieBannerStats::class);

    $this->get('/admin/users/statistiche')->assertOk()
        ->assertSeeLivewire(UsersOverview::class)
        ->assertSeeLivewire(UserActivityChart::class)
        ->assertSeeLivewire(TopCitiesChart::class)
        ->assertSeeLivewire(CookieBannerStats::class);
});

it('has quick filters with counters that never include staff accounts', function () {
    $tabs = (new ListUsers)->getTabs();

    expect(array_keys($tabs))->toBe(['tutti', 'da-verificare', 'hanno-ordinato', 'senza-ordini', 'bloccati'])
        ->and($tabs['tutti']->getBadge())->toBe('3')           // l'admin non conta
        ->and($tabs['da-verificare']->getBadge())->toBe('1')
        ->and($tabs['hanno-ordinato']->getBadge())->toBe('1')
        ->and($tabs['senza-ordini']->getBadge())->toBe('2')
        ->and($tabs['bloccati']->getBadge())->toBe('1');
});

it('filters the table from the quick filters', function () {
    Livewire::test(ListUsers::class)
        ->set('activeTab', 'hanno-ordinato')->assertCanSeeTableRecords([$this->buyer])->assertCountTableRecords(1)
        ->set('activeTab', 'da-verificare')->assertCanSeeTableRecords([$this->unverified])->assertCountTableRecords(1)
        ->set('activeTab', 'bloccati')->assertCanSeeTableRecords([$this->blocked])->assertCountTableRecords(1)
        ->set('activeTab', 'senza-ordini')->assertCountTableRecords(2);
});

it('renders the statistics page', function () {
    Livewire::test(UserStats::class)->assertOk();
});
