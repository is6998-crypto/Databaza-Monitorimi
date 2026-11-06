<?php

namespace App\Models;

use App\Enums\MeasureKind;
use App\Models\Reference\SecurityMeasureType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Security measure, seizure or confiscation — requested and/or granted.
 *
 * @property int $id
 * @property int $case_id
 * @property int|null $case_party_id
 * @property MeasureKind $kind
 * @property int|null $security_measure_type_id
 * @property bool $requested
 * @property Carbon|null $requested_on
 * @property bool|null $granted
 * @property Carbon|null $decided_on
 * @property string|null $amount_eur
 * @property string|null $description
 * @property-read SecurityMeasureType|null $securityMeasureType
 * @property-read CaseParty|null $party
 */
class Measure extends Model
{
    use LogsActivity;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => MeasureKind::class,
            'requested' => 'boolean',
            'granted' => 'boolean',
            'requested_on' => 'date',
            'decided_on' => 'date',
            'amount_eur' => 'decimal:2',
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
     * @return BelongsTo<CaseParty, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'case_party_id');
    }

    /**
     * @return BelongsTo<SecurityMeasureType, $this>
     */
    public function securityMeasureType(): BelongsTo
    {
        return $this->belongsTo(SecurityMeasureType::class);
    }
}
