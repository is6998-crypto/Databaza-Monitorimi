<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name
 */
class Judge extends ReferenceModel
{
    protected $table = 'judges';

    public static function isTranslatable(): bool
    {
        return false;
    }

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
            ['cases', 'judge_id'],
            ['hearings', 'judge_id'],
        ];
    }
}
