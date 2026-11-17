<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A department that exists in a court (e.g. only the Basic Court of Prishtina has the Special Department).
 * A pivot model so that adding and removing a department is written to the audit log.
 *
 * @property int $id
 * @property int $court_id
 * @property int $department_id
 */
class CourtDepartment extends Pivot
{
    use LogsActivity;

    protected $table = 'court_department';

    public $incrementing = true;

    public $timestamps = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('reference')->logAll();
    }
}
