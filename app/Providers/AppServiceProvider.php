<?php

namespace App\Providers;

use App\Listeners\AuditAuthenticationEvents;
use App\Listeners\AuditPermissionChanges;
use App\Models\AdministrativeCaseDetail;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CivilCaseDetail;
use App\Models\Measure;
use App\Models\Sentence;
use App\Models\User;
use App\Observers\RecentValuesObserver;
use App\Policies\ActivityPolicy;
use App\Policies\CaseChildPolicy;
use App\Policies\RolePolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super Admin can do everything (proposal B4). Workflow state rules still apply in the services.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        $this->registerPolicies();

        $this->configureDates();
        $this->configureAuditing();
        $this->configureRateLimiting();

        RecentValuesObserver::register();
    }

    private function registerPolicies(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        foreach ([CaseParty::class, Charge::class, CaseStage::class, Measure::class, Sentence::class, AdministrativeCaseDetail::class, CivilCaseDetail::class] as $child) {
            Gate::policy($child, CaseChildPolicy::class);
        }
    }

    /**
     * Store in UTC, display in Kosovo time as dd.mm.yyyy (proposal B3).
     */
    private function configureDates(): void
    {
        $timezone = (string) config('courtwatch.display_timezone');
        $date = (string) config('courtwatch.date_format');
        $dateTime = (string) config('courtwatch.datetime_format');

        FilamentTimezone::set($timezone);

        Table::configureUsing(fn (Table $table) => $table
            ->defaultDateDisplayFormat($date)
            ->defaultDateTimeDisplayFormat($dateTime));

        Schema::configureUsing(fn (Schema $schema) => $schema
            ->defaultDateDisplayFormat($date)
            ->defaultDateTimeDisplayFormat($dateTime));

        DatePicker::configureUsing(fn (DatePicker $picker) => $picker
            ->native(false)
            ->displayFormat($date)
            ->firstDayOfWeek(1));

        DateTimePicker::configureUsing(fn (DateTimePicker $picker) => $picker
            ->native(false)
            ->firstDayOfWeek(1));
    }

    /**
     * Logins, failed logins, logouts, password resets and permission changes are audited.
     */
    private function configureAuditing(): void
    {
        Event::listen(Login::class, [AuditAuthenticationEvents::class, 'login']);
        Event::listen(Failed::class, [AuditAuthenticationEvents::class, 'failed']);
        Event::listen(Logout::class, [AuditAuthenticationEvents::class, 'logout']);
        Event::listen(PasswordReset::class, [AuditAuthenticationEvents::class, 'passwordReset']);

        Event::listen(RoleAttachedEvent::class, [AuditPermissionChanges::class, 'roleAttached']);
        Event::listen(RoleDetachedEvent::class, [AuditPermissionChanges::class, 'roleDetached']);
        Event::listen(PermissionAttachedEvent::class, [AuditPermissionChanges::class, 'permissionAttached']);
        Event::listen(PermissionDetachedEvent::class, [AuditPermissionChanges::class, 'permissionDetached']);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $perMinute = (int) config('courtwatch.api.rate_limit_per_minute', 60);

            return Limit::perMinute($perMinute)->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });

        RateLimiter::for('portal-search', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('monitor-sync', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
