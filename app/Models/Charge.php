<?php

namespace App\Models;

use App\Enums\ChargeStage;
use App\Models\Reference\CriminalOffence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Party × offence × stage (criminal report, indictment, judgment).
 *
 * @property int $id
 * @property int $case_id
 * @property int $case_party_id
 * @property int $criminal_offence_id
 * @property ChargeStage $stage
 * @property-read CaseParty $party
 * @property-read CriminalOffence $offence
 */
class Charge extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ChargeStage::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $charge): void {
            if ($charge->case_id === null && $charge->case_party_id !== null) {
                $charge->case_id = (int) CaseParty::query()->whereKey($charge->case_party_id)->value('case_id');
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

    /**
     * @return BelongsTo<CriminalOffence, $this>
     */
    public function offence(): BelongsTo
    {
        return $this->belongsTo(CriminalOffence::class, 'criminal_offence_id');
    }
}
