<?php

namespace App\Providers\Filament;

use App\Filament\Support\PinkAvatarProvider;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Notifications\Livewire\Notifications;
use Filament\Pages\BasePage;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('F&L Beauty')
            ->brandLogo(asset('images/logo-mark.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.png'))
            ->defaultAvatarProvider(PinkAvatarProvider::class)
            ->defaultThemeMode(ThemeMode::Light)
            // Menu in alto e contenuto a tutta larghezza: l'area admin sfrutta
            // tutto lo schermo del PC (su tablet/mobile il menu diventa a scomparsa).
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->bootUsing(function () {
                // Salva/Annulla restano sempre visibili in fondo allo schermo, e la
                // conferma del salvataggio compare lì vicino invece che in alto a destra.
                BasePage::stickyFormActions();
                BasePage::formActionsAlignment(Alignment::End);
                Notifications::alignment(Alignment::End);
                Notifications::verticalAlignment(VerticalAlignment::End);
            })
            ->colors([
                'primary' => Color::hex('#B76E79'),
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
                'gray' => Color::hex('#BFA39E'),
            ])
            ->assets([
                Css::make('fl-admin-custom', public_path('css/admin-custom.css'))
                    ->relativePublicPath('css/admin-custom.css'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
