<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
