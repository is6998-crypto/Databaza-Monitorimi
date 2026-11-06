<?php

namespace App\Models\Reference;

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

    public static function references(): array
    {
        return [
            ['divisions', 'department_id'],
            ['cases', 'department_id'],
        ];
    }
}
