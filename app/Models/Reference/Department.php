<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public static function references(): array
    {
        return [
            ['divisions', 'department_id'],
            ['cases', 'department_id'],
        ];
    }
}
