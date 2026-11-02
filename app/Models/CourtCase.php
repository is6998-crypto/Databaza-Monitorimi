<?php

namespace App\Models;

use App\Enums\CaseType;
use App\Enums\StageType;
use App\Models\Concerns\HasWorkflow;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\Department;
use App\Models\Reference\Division;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use Database\Factories\CourtCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A monitored court case. Table "cases" (Case is a reserved word in PHP).
 *
 * @property int $id
 * @property string $public_ref
 * @property CaseType $type
 * @property int|null $court_id
 * @property int|null $court_branch_id
 * @property int|null $department_id
 * @property int|null $division_id
 * @property int|null $judge_id
 * @property int|null $prosecutor_id
 * @property int|null $prosecution_office_id
 * @property int|null $assigned_monitor_id
 * @property string|null $court_case_number
 * @property string|null $indictment_number
 * @property string|null $police_case_number
 * @property string|null $summary
 * @property string|null $legacy_module
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property-read Court|null $court
 * @property-read Judge|null $judge
 * @property-read Prosecutor|null $prosecutor
 * @property-read Monitor|null $assignedMonitor
 * @property-read Collection<int, CaseParty> $parties
 * @property-read Collection<int, CaseStage> $stages
 * @property-read Collection<int, Hearing> $hearings
 * @property-read Collection<int, Charge> $charges
 * @property-read AdministrativeCaseDetail|null $administrativeDetails
 * @property-read CivilCaseDetail|null $civilDetails
 */
class CourtCase extends Model
{
    /** @use HasFactory<CourtCaseFactory> */
    use HasFactory;

    use HasUlids;
    use HasWorkflow;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'cases';

    protected $guarded = ['id', 'public_ref', 'status', 'created_at', 'updated_at', 'deleted_at', 'submitted_at', 'approved_at', 'approved_by', 'published_at', 'returned_at', 'return_comment'];

    /**
     * The ULID column is public_ref, not the primary key.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_ref'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CaseType::class,
            ...$this->workflowCasts(),
        ];
    }

    protected static function newFactory(): CourtCaseFactory
    {
        return CourtCaseFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cases')
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'public_ref'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Short human label for staff screens (never shown publicly).
     */
    public function getDisplayLabelAttribute(): string
    {
        $number = $this->court_case_number ?: ('#'.$this->id);

        return trim($number.' · '.($this->court?->label() ?? ''), ' ·');
    }

    /**
     * Public, non-identifying reference (e.g. "CW-01JD3…").
     */
    public function getPublicCodeAttribute(): string
    {
        return 'CW-'.strtoupper(substr($this->public_ref, -10));
    }

    // ---------------------------------------------------------------- scopes

    /**
     * Records a user may see. Monitors (without broader roles) only see cases in their
     * assigned courts, cases assigned to them and cases they created.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isMonitorOnly()) {
            return;
        }

        $monitor = $user->monitor;
        $courtIds = $monitor?->courts()->pluck('courts.id')->all() ?? [];

        $query->where(function (Builder $query) use ($user, $monitor, $courtIds): void {
            $query->where('cases.created_by', $user->id);

            if ($monitor !== null) {
                $query->orWhere('cases.assigned_monitor_id', $monitor->id);
            }

            if ($courtIds !== []) {
                $query->orWhereIn('cases.court_id', $courtIds);
            }
        });
    }

    // ---------------------------------------------------------------- helpers

    public function stage(StageType $type): ?CaseStage
    {
        return $this->stages->first(fn (CaseStage $stage): bool => $stage->stage === $type);
    }

    /**
     * Other cases with the same court case number in the same court (duplicate warning).
     *
     * @return Builder<static>
     */
    public static function duplicatesOf(?int $courtId, ?string $courtCaseNumber, ?int $ignoreId = null): Builder
    {
        return static::query()
            ->where('court_id', $courtId)
            ->whereRaw('lower(trim(court_case_number)) = ?', [mb_strtolower(trim((string) $courtCaseNumber))])
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId));
    }

    // ---------------------------------------------------------------- relations

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<CourtBranch, $this>
     */
    public function courtBranch(): BelongsTo
    {
        return $this->belongsTo(CourtBranch::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Presiding judge.
     *
     * @return BelongsTo<Judge, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    /**
     * Case prosecutor.
     *
     * @return BelongsTo<Prosecutor, $this>
     */
    public function prosecutor(): BelongsTo
    {
        return $this->belongsTo(Prosecutor::class);
    }

    /**
     * @return BelongsTo<ProsecutionOffice, $this>
     */
    public function prosecutionOffice(): BelongsTo
    {
        return $this->belongsTo(ProsecutionOffice::class);
    }

    /**
     * @return BelongsTo<Monitor, $this>
     */
    public function assignedMonitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class, 'assigned_monitor_id');
    }

    /**
     * @return HasMany<CaseParty, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(CaseParty::class, 'case_id');
    }

    /**
     * @return HasMany<Charge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class, 'case_id');
    }

    /**
     * @return HasMany<CaseStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(CaseStage::class, 'case_id');
    }

    /**
     * @return HasMany<Hearing, $this>
     */
    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class, 'case_id');
    }

    /**
     * @return HasMany<Measure, $this>
     */
    public function measures(): HasMany
    {
        return $this->hasMany(Measure::class, 'case_id');
    }

    /**
     * @return HasMany<Sentence, $this>
     */
    public function sentences(): HasMany
    {
        return $this->hasMany(Sentence::class, 'case_id');
    }

    /**
     * @return HasOne<AdministrativeCaseDetail, $this>
     */
    public function administrativeDetails(): HasOne
    {
        return $this->hasOne(AdministrativeCaseDetail::class, 'case_id');
    }

    /**
     * @return HasOne<CivilCaseDetail, $this>
     */
    public function civilDetails(): HasOne
    {
        return $this->hasOne(CivilCaseDetail::class, 'case_id');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
