<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 */
class CriminalCode extends ReferenceModel
{
    protected $table = 'criminal_codes';

    /**
     * @return HasMany<CriminalOffence, $this>
     */
    public function offences(): HasMany
    {
        return $this->hasMany(CriminalOffence::class);
    }

    public static function references(): array
    {
        return [
            ['criminal_offences', 'criminal_code_id'],
        ];
    }
}
