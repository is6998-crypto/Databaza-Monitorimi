<?php

namespace App\Models;

use App\Enums\Instance;
use App\Enums\SentenceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Requested (by the prosecutor) or imposed (by the court) sentence for one party.
 *
 * @property int $id
 * @property int $case_id
 * @property int $case_party_id
 * @property SentenceKind $kind
 * @property Instance $instance
 * @property int|null $prison_months
 * @property int|null $suspended_months
 * @property string|null $fine_eur
 * @property string|null $supplementary_punishment
 * @property-read CaseParty $party
 */
class Sentence extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $attributes = [
        'instance' => 'first_instance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SentenceKind::class,
            'instance' => Instance::class,
            'prison_months' => 'integer',
            'suspended_months' => 'integer',
            'fine_eur' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $sentence): void {
            if ($sentence->case_id === null && $sentence->case_party_id !== null) {
                $sentence->case_id = (int) CaseParty::query()->whereKey($sentence->case_party_id)->value('case_id');
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('cases')->logAll()->logExcept(['created_at', 'updated_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<CourtCase, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    /**
     * @return BelongsTo<CaseParty, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'case_party_id');
    }
}
