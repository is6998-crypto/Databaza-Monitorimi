<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $name_sq
 * @property string|null $name_en
 * @property string|null $name_sr
 * @property int $court_id
 * @property string|null $city
 */
class CourtBranch extends ReferenceModel
{
    protected $table = 'court_branches';

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Departments this branch has (the branches of the basic courts have the General Department only).
     *
     * @return BelongsToMany<Department, $this, CourtBranchDepartment>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'court_branch_department')->using(CourtBranchDepartment::class);
    }

    public static function references(): array
    {
        return [
            ['cases', 'court_branch_id'],
            ['court_branch_department', 'court_branch_id', ['department_id']],
        ];
    }
}
