<?php

namespace App\Models\Reference;

use App\Enums\CourtLevel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CourtLevel $level
 */
class Court extends ReferenceModel
{
    protected $table = 'courts';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => CourtLevel::class,
        ];
    }

    /**
     * @return HasMany<CourtBranch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(CourtBranch::class);
    }

    /**
     * @return HasMany<Judge, $this>
     */
    public function judges(): HasMany
    {
        return $this->hasMany(Judge::class);
    }

    /**
     * @return BelongsToMany<Monitor, $this>
     */
    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    public static function references(): array
    {
        return [
            ['court_branches', 'court_id'],
            ['judges', 'court_id'],
            ['cases', 'court_id'],
            ['hearings', 'court_id'],
            ['court_monitor', 'court_id', ['monitor_id']],
        ];
    }
}
