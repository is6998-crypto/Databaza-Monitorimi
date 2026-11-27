<?php

namespace App\Models;

use App\Enums\DeadlineRule;
use App\Enums\DeadlineState;
use App\Enums\StageType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One phase of a criminal case that has a legal time limit: when it started, the limit that applies and the day
 * it ends. Rows are derived from the case and its stages by App\Services\Deadlines\DeadlineTracker (never edited
 * by hand), so they are not audited themselves: the stages and the case are.
 *
 * @property int $id
 * @property int $case_id
 * @property DeadlineRule $rule
 * @property DeadlineState $state
 * @property string $basis
 * @property int $limit_amount
 * @property string $limit_unit
 * @property StageType $start_stage
 * @property Carbon $started_on
 * @property Carbon $due_on
 * @property Carbon|null $extended_due_on
 * @property Carbon|null $ended_on
 * @property Carbon|null $warned_at
 * @property Carbon|null $notified_at
 * @property Carbon|null $extended_notified_at
 * @property-read CourtCase $case
 */
class CaseDeadline extends Model
{
    /** Shown statuses: the stored state, with an open phase split by today's date. */
    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_RUNNING = 'running';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rule' => DeadlineRule::class,
            'state' => DeadlineState::class,
            'start_stage' => StageType::class,
            'started_on' => 'date',
            'due_on' => 'date',
            'extended_due_on' => 'date',
            'ended_on' => 'date',
            'warned_at' => 'datetime',
            'notified_at' => 'datetime',
            'extended_notified_at' => 'datetime',
        ];
    }

    /**
     * Today's date in Kosovo. Limits are counted in whole days, so this is that calendar day at midnight in the
     * application's own timezone, like the date columns it is compared with (not midnight in Kosovo, which is
     * still the day before in UTC and would count one day short).
     */
    public static function today(): CarbonInterface
    {
        return Carbon::parse(Carbon::now((string) config('courtwatch.display_timezone'))->toDateString());
    }

    // ---------------------------------------------------------------- scopes

    /**
     * Deadlines of cases the user may see (monitor court scope).
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereHas('case', fn (Builder $case) => $case->visibleTo($user));
    }

    /**
     * Still running and past the limit.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        static::constrain($query, self::STATUS_OVERDUE);
    }

    /**
     * Still running and ending within the next days (config deadlines.due_soon_days), today included.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDueSoon(Builder $query): void
    {
        static::constrain($query, self::STATUS_DUE_SOON);
    }

    /**
     * Still running with more time left than "due soon".
     *
     * @param  Builder<static>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        static::constrain($query, self::STATUS_RUNNING);
    }

    /**
     * Narrows a query on this table to one shown status (overdue, due_soon, running) or one stored state.
     *
     * @param  Builder<*>  $query
     */
    public static function constrain(Builder $query, string $status): void
    {
        $today = static::today();
        $soon = $today->copy()->addDays((int) config('deadlines.due_soon_days', 7))->toDateString();
        $open = fn () => $query->where('case_deadlines.state', DeadlineState::Open->value);

        match ($status) {
            self::STATUS_OVERDUE => $open()->whereDate('case_deadlines.due_on', '<', $today->toDateString()),
            self::STATUS_DUE_SOON => $open()->whereDate('case_deadlines.due_on', '>=', $today->toDateString())->whereDate('case_deadlines.due_on', '<=', $soon),
            self::STATUS_RUNNING => $open()->whereDate('case_deadlines.due_on', '>', $soon),
            default => $query->where('case_deadlines.state', $status),
        };
    }

    // ---------------------------------------------------------------- helpers

    public function isOverdue(): bool
    {
        return $this->state === DeadlineState::Open && $this->due_on->lt(static::today());
    }

    public function isDueSoon(): bool
    {
        return $this->state === DeadlineState::Open
            && ! $this->isOverdue()
            && $this->due_on->lte(static::today()->copy()->addDays((int) config('deadlines.due_soon_days', 7)));
    }

    /**
     * overdue, due_soon, running, met, late, closed or paused.
     */
    public function status(): string
    {
        return match (true) {
            $this->isOverdue() => self::STATUS_OVERDUE,
            $this->isDueSoon() => self::STATUS_DUE_SOON,
            $this->state === DeadlineState::Open => self::STATUS_RUNNING,
            default => $this->state->value,
        };
    }

    /**
     * Days past the limit: until today for a running phase, until its end for a finished one. Zero if within it.
     */
    public function daysOver(): int
    {
        $until = $this->state === DeadlineState::Open ? static::today() : $this->ended_on;

        if ($until === null || $until->lte($this->due_on)) {
            return 0;
        }

        return (int) $this->due_on->diffInDays($until, true);
    }

    /**
     * Days left of a running phase (0 on the last day, negative once over).
     */
    public function daysLeft(): int
    {
        return (int) static::today()->diffInDays($this->due_on, false);
    }

    /**
     * Length of the limit in days (from the day the phase started to the day the limit ends).
     */
    public function limitDays(): int
    {
        return max(1, (int) $this->started_on->diffInDays($this->due_on, true));
    }

    /**
     * Days the phase has run: until today while it is open, until its end once it has one.
     */
    public function elapsedDays(): int
    {
        $until = $this->ended_on ?? ($this->state === DeadlineState::Open ? static::today() : $this->due_on);

        return max(0, (int) $this->started_on->diffInDays($until, false));
    }

    /**
     * How much of the limit is used, in percent (0–100; 100 once it is exceeded).
     */
    public function usedPercent(): int
    {
        return (int) min(100, round($this->elapsedDays() / $this->limitDays() * 100));
    }

    /**
     * For a bar that shows the limit and the days over it side by side: the limit's share of the whole, in
     * percent (never under 12, so that a short limit stays visible beside a long delay).
     */
    public function limitShare(): int
    {
        $over = $this->daysOver();

        return $over === 0 ? 100 : (int) max(12, round($this->limitDays() / ($this->limitDays() + $over) * 100));
    }

    /**
     * The day from which the people responsible are warned that the limit is running out: when the share of the
     * limit in config deadlines.warning.used_percent is used, but never more than warning.max_days_before days
     * ahead. Null for limits too short to warn about (warning.min_limit_days).
     */
    public function warnOn(): ?Carbon
    {
        $limit = $this->limitDays();

        if ($limit < (int) config('deadlines.warning.min_limit_days', 7)) {
            return null;
        }

        $percent = min(99, max(1, (int) config('deadlines.warning.used_percent', 80)));
        $before = min((int) config('deadlines.warning.max_days_before', 30), max(1, (int) round($limit * (100 - $percent) / 100)));

        return $this->due_on->copy()->subDays($before);
    }

    /**
     * Still running, not yet over the limit, and past the day of the warning.
     */
    public function needsWarning(): bool
    {
        $warnOn = $this->warnOn();
        $today = static::today();

        return $this->state === DeadlineState::Open
            && $warnOn !== null
            && $today->gte($warnOn)
            && $this->due_on->gte($today);
    }

    /**
     * Whether the longest limit the law allows has passed too (complex case, extended investigation).
     */
    public function isPastExtension(): bool
    {
        return $this->state === DeadlineState::Open
            && $this->extended_due_on !== null
            && $this->extended_due_on->lt(static::today());
    }

    /**
     * "30 ditë", "3 muaj", "2 vjet".
     */
    public function limitLabel(?string $locale = null): string
    {
        return trans_choice("deadlines.units.{$this->limit_unit}", $this->limit_amount, ['count' => $this->limit_amount], $locale);
    }

    /**
     * Why this limit applies ("i akuzuari në paraburgim"), or null for the standard limit.
     */
    public function basisLabel(?string $locale = null): ?string
    {
        return $this->basis === 'standard' ? null : (string) __("deadlines.basis.{$this->basis}", [], $locale);
    }

    /**
     * Status with its number of days, e.g. "Tejkaluar · 12 ditë".
     */
    public function statusLabel(?string $locale = null): string
    {
        $status = $this->status();
        $label = (string) __("deadlines.status.{$status}", [], $locale);

        $days = match ($status) {
            self::STATUS_OVERDUE, DeadlineState::Late->value => $this->daysOver(),
            self::STATUS_DUE_SOON, self::STATUS_RUNNING => $this->daysLeft(),
            default => null,
        };

        if ($days === null) {
            return $label;
        }

        if ($days === 0 && $status !== self::STATUS_OVERDUE && $status !== DeadlineState::Late->value) {
            return $label.' · '.__('deadlines.last_day', [], $locale);
        }

        return $label.' · '.trans_choice('deadlines.units.days', $days, ['count' => $days], $locale);
    }

    public function statusColor(): string
    {
        return match ($this->status()) {
            self::STATUS_OVERDUE => 'primary',
            self::STATUS_DUE_SOON => 'warning',
            DeadlineState::Late->value => 'danger',
            DeadlineState::Met->value => 'success',
            default => 'gray',
        };
    }

    // ---------------------------------------------------------------- relations

    /**
     * @return BelongsTo<CourtCase, $this>
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }
}
