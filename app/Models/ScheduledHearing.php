<?php

namespace App\Models;

use App\Enums\ScheduledHearingStatus;
use App\Models\Reference\Monitor;
use App\Support\CaseAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A court session that is coming up (the hearing calendar): when, where, who will attend, and — once the day has
 * passed — whether it was monitored. Planning data for the staff only; it is not reviewed and never public.
 *
 * @property int $id
 * @property int $case_id
 * @property Carbon $scheduled_on
 * @property string|null $scheduled_time
 * @property string|null $courtroom
 * @property string|null $note
 * @property int|null $monitor_id
 * @property int|null $source_hearing_id
 * @property int|null $hearing_id
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $reminded_at
 * @property Carbon|null $unstaffed_notified_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read CourtCase $case
 * @property-read Monitor|null $monitor
 * @property-read Hearing|null $sourceHearing
 * @property-read Hearing|null $hearing
 * @property-read User|null $creator
 */
class ScheduledHearing extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at', 'reminded_at', 'unstaffed_notified_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'cancelled_at' => 'datetime',
            'reminded_at' => 'datetime',
            'unstaffed_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $scheduled): void {
            $scheduled->created_by ??= auth()->id();
        });

        static::saving(function (self $scheduled): void {
            if (auth()->id() !== null) {
                $scheduled->updated_by = (int) auth()->id();
            }

            // A new day or person means a new reminder (and a new warning if nobody attends).
            if ($scheduled->exists && $scheduled->isDirty(['scheduled_on', 'scheduled_time', 'monitor_id'])) {
                $scheduled->reminded_at = null;
            }

            if ($scheduled->exists && $scheduled->isDirty('scheduled_on')) {
                $scheduled->unstaffed_notified_at = null;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('calendar')
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'reminded_at', 'unstaffed_notified_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------------------------------------------------------- scopes

    /**
     * Planned hearings of the cases the user may see (monitor court scope).
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereHas('case', fn (Builder $case) => $case->visibleTo($user));
    }

    /**
     * Not cancelled and not yet monitored.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        static::constrain($query, 'open');
    }

    /**
     * Open, from today on (in Kosovo).
     *
     * @param  Builder<static>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        static::constrain($query, ScheduledHearingStatus::Upcoming->value);
    }

    /**
     * Open hearings whose day passed without a monitored hearing being recorded.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMissed(Builder $query): void
    {
        static::constrain($query, ScheduledHearingStatus::Missed->value);
    }

    /**
     * Soonest first, then by time (hearings without a time last on their day).
     *
     * @param  Builder<static>  $query
     */
    public function scopeChronological(Builder $query): void
    {
        static::sortChronologically($query);
    }

    /**
     * Narrows a query on this table to open hearings or to one status (upcoming, missed, attended, cancelled).
     *
     * @param  Builder<*>  $query
     */
    public static function constrain(Builder $query, string $status): void
    {
        $today = static::today()->toDateString();
        $open = fn () => $query->whereNull('scheduled_hearings.cancelled_at')->whereNull('scheduled_hearings.hearing_id');

        match ($status) {
            'open' => $open(),
            ScheduledHearingStatus::Upcoming->value => $open()->whereDate('scheduled_hearings.scheduled_on', '>=', $today),
            ScheduledHearingStatus::Missed->value => $open()->whereDate('scheduled_hearings.scheduled_on', '<', $today),
            ScheduledHearingStatus::Attended->value => $query->whereNotNull('scheduled_hearings.hearing_id'),
            ScheduledHearingStatus::Cancelled->value => $query->whereNotNull('scheduled_hearings.cancelled_at')->whereNull('scheduled_hearings.hearing_id'),
            default => $query,
        };
    }

    /**
     * @param  Builder<*>  $query
     */
    public static function sortChronologically(Builder $query): void
    {
        $query->orderBy('scheduled_hearings.scheduled_on')
            ->orderByRaw('scheduled_hearings.scheduled_time is null')
            ->orderBy('scheduled_hearings.scheduled_time');
    }

    // ---------------------------------------------------------------- helpers

    public static function today(): Carbon
    {
        return Carbon::parse(Carbon::now((string) config('courtwatch.display_timezone'))->toDateString());
    }

    public function status(): ScheduledHearingStatus
    {
        return match (true) {
            $this->hearing_id !== null => ScheduledHearingStatus::Attended,
            $this->cancelled_at !== null => ScheduledHearingStatus::Cancelled,
            $this->scheduled_on->lt(static::today()) => ScheduledHearingStatus::Missed,
            default => ScheduledHearingStatus::Upcoming,
        };
    }

    /**
     * "12.12.2026, 10:30".
     */
    public function whenLabel(): string
    {
        $date = $this->scheduled_on->format((string) config('courtwatch.date_format'));

        return $this->scheduled_time ? $date.', '.$this->scheduled_time : $date;
    }

    public function isAssignedTo(User $user): bool
    {
        return $user->monitor !== null && $this->monitor_id === $user->monitor->id;
    }

    public function canBeSeenBy(User $user): bool
    {
        return CaseAccess::canSeeCase($user, $this->case);
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
     * @return BelongsTo<Hearing, $this>
     */
    public function sourceHearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class, 'source_hearing_id');
    }

    /**
     * @return BelongsTo<Hearing, $this>
     */
    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class, 'hearing_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
