<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A department that exists in a court branch (branches of the basic courts have the General Department only).
 *
 * @property int $id
 * @property int $court_branch_id
 * @property int $department_id
 */
class CourtBranchDepartment extends Pivot
{
    use LogsActivity;

    protected $table = 'court_branch_department';

    public $incrementing = true;

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('reference')->logAll();
    }
}
