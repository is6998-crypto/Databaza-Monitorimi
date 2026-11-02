<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Division extends ReferenceModel
{
    protected $table = 'divisions';

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public static function references(): array
    {
        return [
            ['cases', 'division_id'],
        ];
    }
}
