<?php

namespace App\Models;

use App\Enums\Appellant;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Models\Reference\Applicant;
use App\Models\Reference\Prosecutor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One milestone of a case (criminal report, indictment, judgment, appeal…).
 *
 * @property int $id
 * @property int $case_id
 * @property StageType $stage
 * @property Carbon|null $date
 * @property StageOutcome|null $outcome
 * @property int|null $applicant_id
 * @property int|null $prosecutor_id
 * @property bool $is_direct_indictment
 * @property Appellant|null $appellant
 * @property string|null $notes
 * @property-read Applicant|null $applicant
 * @property-read Prosecutor|null $prosecutor
 */
class CaseStage extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => StageType::class,
            'outcome' => StageOutcome::class,
            'appellant' => Appellant::class,
            'date' => 'date',
            'is_direct_indictment' => 'boolean',
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

    /**
     * Who filed the criminal report.
     *
     * @return BelongsTo<Applicant, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Prosecutor who drafted the indictment.
     *
     * @return BelongsTo<Prosecutor, $this>
     */
    public function prosecutor(): BelongsTo
    {
        return $this->belongsTo(Prosecutor::class);
    }
}
