<?php

namespace App\Models\Reference;

use App\Enums\ProsecutionLevel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name_sq
 * @property string|null $name_en
 * @property string|null $name_sr
 * @property string|null $city
 * @property ProsecutionLevel $level
 */
class ProsecutionOffice extends ReferenceModel
{
    protected $table = 'prosecution_offices';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => ProsecutionLevel::class,
        ];
    }

    /**
     * @return HasMany<Prosecutor, $this>
     */
    public function prosecutors(): HasMany
    {
        return $this->hasMany(Prosecutor::class);
    }

    public static function references(): array
    {
        return [
            ['prosecutors', 'prosecution_office_id'],
            ['cases', 'prosecution_office_id'],
        ];
    }
}
