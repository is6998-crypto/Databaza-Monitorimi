<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $name
 */
class Prosecutor extends ReferenceModel
{
    protected $table = 'prosecutors';

    public static function isTranslatable(): bool
    {
        return false;
    }

    /**
     * @return BelongsTo<ProsecutionOffice, $this>
     */
    public function prosecutionOffice(): BelongsTo
    {
        return $this->belongsTo(ProsecutionOffice::class);
    }

    public static function references(): array
    {
        return [
            ['cases', 'prosecutor_id'],
            ['hearings', 'prosecutor_id'],
            ['case_stages', 'prosecutor_id'],
        ];
    }
}
