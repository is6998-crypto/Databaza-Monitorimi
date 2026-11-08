<?php

namespace App\Models;

use App\Enums\HearingType;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Models\Concerns\HasWorkflow;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\Prosecutor;
use Database\Factories\HearingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One monitored court session.
 *
 * Multi-select answers (postponement reasons, phone users, participants) are stored as
 * JSON arrays of enum values; see docs/DECISIONS.md.
 *
 * @property int $id
 * @property string $public_ref
 * @property int $case_id
 * @property int|null $monitor_id
 * @property Carbon $hearing_date
 * @property HearingType|null $hearing_type
 * @property int|null $court_id
 * @property int|null $judge_id
 * @property int|null $prosecutor_id
 * @property Venue|null $venue
 * @property bool|null $is_public
 * @property bool $started_late
 * @property int|null $delay_minutes
 * @property list<string>|null $delay_causes
 * @property bool|null $plea_agreement
 * @property bool $was_postponed
 * @property list<string>|null $postponement_reasons
 * @property bool $phone_used
 * @property list<string>|null $phone_users
 * @property bool|null $security_adequate
 * @property string|null $security_comment
 * @property Recording|null $recording
 * @property bool $interpretation_needed
 * @property bool|null $interpretation_provided
 * @property list<string>|null $participants
 * @property string|null $notes
 * @property string|null $client_uuid
 * @property-read CourtCase $case
 * @property-read Monitor|null $monitor
 * @property-read Court|null $court
 * @property-read Judge|null $judge
 * @property-read Prosecutor|null $prosecutor
 */
class Hearing extends Model
{
    /** @use HasFactory<HearingFactory> */
    use HasFactory;

    use HasUlids;
    use HasWorkflow;
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'public_ref', 'status', 'created_at', 'updated_at', 'deleted_at', 'submitted_at', 'approved_at', 'approved_by', 'published_at', 'returned_at', 'return_comment'];

    /**
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
            'hearing_date' => 'date',
            'hearing_type' => HearingType::class,
            'venue' => Venue::class,
            'is_public' => 'boolean',
            'started_late' => 'boolean',
            'delay_causes' => 'array',
            'plea_agreement' => 'boolean',
            'was_postponed' => 'boolean',
            'postponement_reasons' => 'array',
            'phone_used' => 'boolean',
            'phone_users' => 'array',
            'security_adequate' => 'boolean',
            'recording' => Recording::class,
            'interpretation_needed' => 'boolean',
            'interpretation_provided' => 'boolean',
            'participants' => 'array',
            ...$this->workflowCasts(),
        ];
    }

    protected static function booted(): void
    {
        // Conditional answers are cleared when their trigger question is answered "no".
        static::saving(function (self $hearing): void {
            if (! $hearing->started_late) {
                $hearing->delay_minutes = null;
                $hearing->delay_causes = null;
            }

            if (! $hearing->was_postponed) {
                $hearing->postponement_reasons = null;
            }

            if (! $hearing->phone_used) {
                $hearing->phone_users = null;
            }

            if (! $hearing->interpretation_needed) {
                $hearing->interpretation_provided = null;
            }
        });
    }

    protected static function newFactory(): HearingFactory
    {
        return HearingFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('hearings')
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'public_ref'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Hearings of cases the user may see (monitor court scope).
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isMonitorOnly()) {
            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            $query->where('hearings.created_by', $user->id)
                ->orWhereHas('case', fn (Builder $case) => $case->visibleTo($user));
        });
    }

    // ---------------------------------------------------------------- relations

    /**
     * @return BelongsTo<CourtCase, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    /**
     * @return BelongsTo<Monitor, $this>
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<Judge, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    /**
     * @return BelongsTo<Prosecutor, $this>
     */
    public function prosecutor(): BelongsTo
    {
        return $this->belongsTo(Prosecutor::class);
    }

    /**
     * Case parties present at this hearing.
     *
     * @return BelongsToMany<CaseParty, $this>
     */
    public function presentParties(): BelongsToMany
    {
        return $this->belongsToMany(CaseParty::class, 'hearing_party');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
