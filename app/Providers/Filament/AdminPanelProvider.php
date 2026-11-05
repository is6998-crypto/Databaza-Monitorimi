<?php

namespace App\Providers\Filament;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Auth\Login;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\SetLocale;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Staff panel (/admin): Super Admin, Editor, Monitor, Analyst. TOTP 2FA is mandatory here.
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Navigation groups with labels resolved at render time, so they follow the user's
     * language (an enum passed directly would be translated once, at boot).
     *
     * @return array<string, FilamentNavigationGroup>
     */
    public static function navigationGroups(): array
    {
        $groups = [];

        foreach (NavigationGroup::cases() as $case) {
            $groups[$case->name] = FilamentNavigationGroup::make()->label(fn (): string => $case->getLabel());
        }

        return $groups;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('CourtWatch')
            ->favicon(asset('icons/favicon.svg'))
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->login(Login::class)
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('CourtWatch')],
                isRequired: true,
            )
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->navigationGroups(self::navigationGroups())
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(NavigationGroup::Administration)
                    ->navigationSort(20),
            ])
            ->userMenuItems([
                Action::make('locale-sq')
                    ->label('Shqip')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn (): string => route('locale.switch', 'sq'))
                    ->visible(fn (): bool => app()->getLocale() !== 'sq'),
                Action::make('locale-en')
                    ->label('English')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn (): string => route('locale.switch', 'en'))
                    ->visible(fn (): bool => app()->getLocale() !== 'en'),
                Action::make('monitor-app')
                    ->label(fn (): string => __('app.monitor_app'))
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url(fn (): string => route('monitor.app')),
            ])
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('partials.idle-timeout', [
                'logoutUrl' => route('filament.admin.auth.logout'),
                'loginUrl' => route('filament.admin.auth.login'),
            ]))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
