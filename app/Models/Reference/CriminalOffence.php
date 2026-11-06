<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An article of a criminal code (KPRK 2019, KPK 2012, UNMIK Regulation…).
 *
 * @property int $criminal_code_id
 * @property string $article_number
 * @property string $title_sq
 * @property string|null $title_en
 * @property string|null $chapter
 * @property-read CriminalCode|null $criminalCode
 * @property-read Category|null $category
 */
class CriminalOffence extends ReferenceModel
{
    protected $table = 'criminal_offences';

    public static function labelPrefix(): string
    {
        return 'title';
    }

    public static function references(): array
    {
        return [
            ['charges', 'criminal_offence_id', ['case_party_id', 'stage']],
        ];
    }

    public function label(?string $locale = null): string
    {
        $code = $this->criminalCode?->code;
        $article = trim(($code ? $code.' ' : '').'art. '.$this->article_number);

        return $article.' — '.parent::label($locale);
    }

    public static function selectOptions(bool $onlyActive = true): array
    {
        $query = static::query()->with('criminalCode')->orderBy('criminal_code_id')->orderByRaw('length(article_number), article_number');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get()->mapWithKeys(fn (self $offence): array => [$offence->id => $offence->label()])->all();
    }

    /**
     * @return BelongsTo<CriminalCode, $this>
     */
    public function criminalCode(): BelongsTo
    {
        return $this->belongsTo(CriminalCode::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
