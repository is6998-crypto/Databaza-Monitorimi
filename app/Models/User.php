<?php

namespace App\Models;

use App\Enums\ResearcherStatus;
use App\Enums\Role;
use App\Models\Reference\Monitor;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use SensitiveParameter;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $locale
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property string|null $app_authentication_secret
 * @property array<string>|null $app_authentication_recovery_codes
 * @property-read Monitor|null $monitor
 * @property-read ResearcherProfile|null $researcherProfile
 */
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasLocalePreference, HasName
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    protected $attributes = [
        'locale' => 'sq',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly(['name', 'email', 'locale', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------------------------------------------------------- panels

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isStaff(),
            'research' => $this->hasRole(Role::Researcher->value),
            default => false,
        };
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(Role::staffValues());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    /**
     * A monitor without any broader staff role: limited to assigned courts and own records.
     */
    public function isMonitorOnly(): bool
    {
        return $this->hasRole(Role::Monitor->value)
            && ! $this->hasAnyRole([Role::SuperAdmin->value, Role::Editor->value, Role::Analyst->value]);
    }

    public function isApprovedResearcher(): bool
    {
        $profile = $this->researcherProfile;

        return $this->is_active
            && $this->hasRole(Role::Researcher->value)
            && $profile !== null
            && $profile->status === ResearcherStatus::Approved
            && $profile->hasAcceptedCurrentAgreement();
    }

    public function hasMultiFactorAuthentication(): bool
    {
        return filled($this->app_authentication_secret);
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: (string) config('app.locale');
    }

    // ---------------------------------------------------------------- MFA (TOTP)

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();

        activity('auth')
            ->causedBy($this)
            ->performedOn($this)
            ->event($secret === null ? 'mfa_disabled' : 'mfa_enabled')
            ->log($secret === null ? 'Two-factor authentication removed' : 'Two-factor authentication set up');
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    // ---------------------------------------------------------------- relations

    /**
     * @return HasOne<Monitor, $this>
     */
    public function monitor(): HasOne
    {
        return $this->hasOne(Monitor::class);
    }

    /**
     * @return HasOne<ResearcherProfile, $this>
     */
    public function researcherProfile(): HasOne
    {
        return $this->hasOne(ResearcherProfile::class);
    }

    /**
     * @return HasMany<SavedFilter, $this>
     */
    public function savedFilters(): HasMany
    {
        return $this->hasMany(SavedFilter::class);
    }

    /**
     * @return HasMany<ExportLog, $this>
     */
    public function exportLogs(): HasMany
    {
        return $this->hasMany(ExportLog::class);
    }
}
