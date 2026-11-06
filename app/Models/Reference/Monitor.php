<?php

namespace App\Models\Reference;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A court monitor. Each monitor is linked to a users row; the display name is the user's name.
 *
 * @property int $user_id
 * @property string|null $code
 * @property-read User|null $user
 */
class Monitor extends ReferenceModel
{
    protected $table = 'monitors';

    public static function isTranslatable(): bool
    {
        return false;
    }

    public static function labelColumn(): string
    {
        return 'code';
    }

    public static function references(): array
    {
        return [
            ['cases', 'assigned_monitor_id'],
            ['hearings', 'monitor_id'],
            ['court_monitor', 'monitor_id', ['court_id']],
        ];
    }

    public function label(?string $locale = null): string
    {
        $name = $this->user->name ?? '—';

        return $this->code ? "{$name} ({$this->code})" : $name;
    }

    public static function selectOptions(bool $onlyActive = true): array
    {
        $query = static::query()->with('user')->orderBy('code');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get()->mapWithKeys(fn (self $monitor): array => [$monitor->id => $monitor->label()])->all();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Courts this monitor is scoped to.
     *
     * @return BelongsToMany<Court, $this>
     */
    public function courts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class);
    }
}
