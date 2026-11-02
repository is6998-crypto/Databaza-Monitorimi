<?php

namespace App\Models;

use App\Enums\LegalRemedyType;
use App\Enums\PlaintiffType;
use App\Enums\Representation;
use App\Enums\SecondInstanceOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Administrative-case specific fields (first/second instance decisions, administrative conflict).
 *
 * @property int $case_id
 * @property string|null $first_instance_decision
 * @property Carbon|null $first_instance_decision_date
 * @property bool|null $decision_reasoned
 * @property Carbon|null $decision_received_date
 * @property LegalRemedyType|null $legal_remedy_type
 * @property Carbon|null $legal_remedy_date
 * @property SecondInstanceOutcome|null $second_instance_outcome
 * @property Carbon|null $second_instance_decision_date
 * @property bool $conflict_filed
 * @property Carbon|null $conflict_filed_date
 * @property PlaintiffType|null $plaintiff_type
 * @property string|null $respondent_body
 * @property Representation|null $representation
 * @property Carbon|null $first_review_date
 * @property Carbon|null $last_review_date
 */
class AdministrativeCaseDetail extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_instance_decision_date' => 'date',
            'decision_reasoned' => 'boolean',
            'decision_received_date' => 'date',
            'legal_remedy_type' => LegalRemedyType::class,
            'legal_remedy_date' => 'date',
            'second_instance_outcome' => SecondInstanceOutcome::class,
            'second_instance_decision_date' => 'date',
            'conflict_filed' => 'boolean',
            'conflict_filed_date' => 'date',
            'plaintiff_type' => PlaintiffType::class,
            'representation' => Representation::class,
            'first_review_date' => 'date',
            'last_review_date' => 'date',
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
