<?php

namespace App\Providers\Filament;

use App\Filament\Research\Pages\Auth\Register;
use App\Filament\Research\Pages\Dashboard;
use App\Filament\Support\InitialsAvatarProvider;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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
 * Researcher area (/research): self-registration, approval status, data-use agreement,
 * anonymized dataset downloads and API tokens. Researchers are not staff: 2FA is offered
 * but not required here (see docs/DECISIONS.md).
 */
class ResearchPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('research')
            ->path('research')
            ->brandName(fn (): string => 'CourtWatch · '.__('research.panel'))
            ->favicon(asset('icons/favicon.svg'))
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName('CourtWatch')],
                isRequired: false,
            )
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverPages(in: app_path('Filament/Research/Pages'), for: 'App\Filament\Research\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
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
                Action::make('api-docs')
                    ->label(fn (): string => __('research.dashboard.api_docs'))
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->url(fn (): string => url('/docs/api'), shouldOpenInNewTab: true),
            ])
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('partials.idle-timeout', [
                'logoutUrl' => route('filament.research.auth.logout'),
                'loginUrl' => route('filament.research.auth.login'),
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
