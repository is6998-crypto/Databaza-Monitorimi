<?php

namespace App\Models;

use App\Enums\CivilOutcome;
use App\Enums\ClaimType;
use App\Enums\PlaintiffType;
use App\Enums\Representation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Civil- and economic-case specific fields.
 *
 * @property int $case_id
 * @property ClaimType|null $claim_type
 * @property string|null $claim_value_eur
 * @property Carbon|null $filed_date
 * @property PlaintiffType|null $plaintiff_type
 * @property PlaintiffType|null $respondent_type
 * @property Representation|null $representation
 * @property bool|null $legal_aid
 * @property bool|null $mediation_attempted
 * @property CivilOutcome|null $outcome
 * @property Carbon|null $decision_date
 */
class CivilCaseDetail extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claim_type' => ClaimType::class,
            'claim_value_eur' => 'decimal:2',
            'filed_date' => 'date',
            'plaintiff_type' => PlaintiffType::class,
            'respondent_type' => PlaintiffType::class,
            'representation' => Representation::class,
            'legal_aid' => 'boolean',
            'mediation_attempted' => 'boolean',
            'outcome' => CivilOutcome::class,
            'decision_date' => 'date',
        ];
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
}
