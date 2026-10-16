<?php

use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\WelcomeCodes\WelcomeCodeResource;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

it('keeps welcome codes out of the top menu: they are a tab inside Sconti', function () {
    expect(WelcomeCodeResource::shouldRegisterNavigation())->toBeFalse()
        ->and(DiscountResource::shouldRegisterNavigation())->toBeTrue()
        ->and(collect(DiscountResource::subNavigation())->map->getLabel()->all())->toBe(['Codici sconto', 'Codici di benvenuto']);

    $this->get('/admin/discounts')->assertOk()->assertSee('Codici di benvenuto')->assertSee('Codici sconto');
    $this->get('/admin/codici-di-benvenuto')->assertOk()->assertSee('Codici sconto')->assertSee('Codici di benvenuto');
});

it('lights up Sconti in the menu also on the welcome codes tab', function () {
    $patterns = (array) DiscountResource::getNavigationItemActiveRoutePattern();

    expect($patterns)->toHaveCount(2)
        ->and(collect($patterns)->contains(fn ($p) => str_contains($p, 'codici-di-benvenuto')))->toBeTrue();
});

it('shows how many welcome codes are about to expire as a badge on the tab', function () {
    $customer = User::factory()->create();
    Discount::create(['code' => 'BENVENUTO-AAAAAA', 'kind' => 'welcome', 'user_id' => $customer->id, 'type' => 'percentuale', 'value' => 15, 'ends_at' => now()->addDays(3), 'is_active' => true]);

    $badge = collect(DiscountResource::subNavigation())->firstWhere(fn ($item) => $item->getLabel() === 'Codici di benvenuto')->getBadge();

    expect((string) $badge)->toBe('1');
});

it('has the Italian text for the notifications bell (no raw translation keys)', function () {
    expect(trans_choice('filament-panels::layout.actions.open_database_notifications.label_with_unread_count', 3, ['count' => 3]))->toBe('Notifiche, 3 da leggere')
        ->and(__('filament-notifications::database.modal.unread_label'))->toBe('Da leggere');
});
