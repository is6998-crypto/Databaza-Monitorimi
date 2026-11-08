<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A statistics category of criminal offences (KORR, DR, JT…; see App\Services\Reference\OffenceCatalogue).
 *
 * @property string|null $code
 * @property string $name_sq
 * @property string|null $name_en
 */
class Category extends ReferenceModel
{
    protected $table = 'categories';

    public static function references(): array
    {
        return [
            ['criminal_offences', 'category_id'],
        ];
    }

    /**
     * @return HasMany<CriminalOffence, $this>
     */
    public function offences(): HasMany
    {
        return $this->hasMany(CriminalOffence::class);
    }
}
