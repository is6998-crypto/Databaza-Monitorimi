<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public static function references(): array
    {
        return [
            ['cases', 'court_branch_id'],
        ];
    }
}
