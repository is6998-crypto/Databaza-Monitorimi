<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name_sq
 * @property string|null $name_en
 * @property string|null $name_sr
 * @property string|null $code
 */
class Department extends ReferenceModel
{
    protected $table = 'departments';

    /**
     * @return HasMany<Division, $this>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    /**
     * @return BelongsToMany<Court, $this, CourtDepartment>
     */
    public function courts(): BelongsToMany
    {
        return $this->belongsToMany(Court::class, 'court_department')->using(CourtDepartment::class);
    }

    /**
     * Ids of the departments that exist in a court or, when a branch is chosen, in that branch.
     * Null means "not restricted": nothing is listed for that court or branch, so every department is offered.
     *
     * @return list<int>|null
     */
    public static function idsAvailableIn(int|string|null $courtId, int|string|null $branchId = null): ?array
    {
        if (filled($branchId)) {
            $ids = CourtBranchDepartment::query()->where('court_branch_id', $branchId)->pluck('department_id');

            if ($ids->isNotEmpty()) {
                return $ids->map(fn ($id): int => (int) $id)->all();
            }
        }

        if (filled($courtId)) {
            $ids = CourtDepartment::query()->where('court_id', $courtId)->pluck('department_id');

            if ($ids->isNotEmpty()) {
                return $ids->map(fn ($id): int => (int) $id)->all();
            }
        }

        return null;
    }

    public static function references(): array
    {
        return [
            ['divisions', 'department_id'],
            ['cases', 'department_id'],
            ['court_department', 'department_id', ['court_id']],
            ['court_branch_department', 'department_id', ['court_branch_id']],
        ];
    }
}
