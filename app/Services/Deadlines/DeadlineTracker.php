<?php

namespace App\Services\Deadlines;

use App\Enums\CaseType;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keeps case_deadlines in step with the cases: called whenever a case or one of its stages is saved or removed,
 * and for every case by the daily check.
 */
class DeadlineTracker
{
    public function __construct(private readonly DeadlineCalculator $calculator) {}

    /**
     * @param  bool  $reload  false when the stages were loaded a moment ago (the daily check)
     */
    public function refresh(CourtCase $case, bool $reload = true): void
    {
        $reload ? $case->load('stages') : $case->loadMissing('stages');

        $computed = $this->calculator->forCase($case);
        $existing = CaseDeadline::query()->where('case_id', $case->id)->get()->keyBy(fn (CaseDeadline $deadline): string => $deadline->rule->value);

        foreach ($computed as $rule => $values) {
            /** @var CaseDeadline $deadline */
            $deadline = $existing->get($rule) ?? new CaseDeadline(['case_id' => $case->id]);

            $dueOn = $values['due_on']->toDateString();
            $extendedDueOn = $values['extended_due_on']?->toDateString();

            // A limit that moved (a corrected date, detention recorded) may run out or be exceeded anew: tell people again then.
            if ($deadline->exists && $deadline->due_on->toDateString() !== $dueOn) {
                $deadline->warned_at = null;
                $deadline->notified_at = null;
            }

            if ($deadline->exists && $deadline->extended_due_on?->toDateString() !== $extendedDueOn) {
                $deadline->extended_notified_at = null;
            }

            $deadline->fill([
                'rule' => $values['rule'],
                'state' => $values['state'],
                'basis' => $values['basis'],
                'limit_amount' => $values['limit_amount'],
                'limit_unit' => $values['limit_unit'],
                'start_stage' => $values['start_stage'],
                'started_on' => $values['started_on']->toDateString(),
                'due_on' => $dueOn,
                'extended_due_on' => $extendedDueOn,
                'ended_on' => $values['ended_on']?->toDateString(),
            ]);

            if ($deadline->isDirty()) {
                $deadline->save();
            }
        }

        $obsolete = $existing->keys()->diff(array_keys($computed));

        if ($obsolete->isNotEmpty()) {
            CaseDeadline::query()->where('case_id', $case->id)->whereIn('rule', $obsolete->all())->delete();
        }
    }

    /**
     * Recomputes every criminal case (a safety net for records written without model events, such as imports).
     *
     * @return int number of cases looked at
     */
    public function refreshAll(): int
    {
        $count = 0;

        CourtCase::query()
            ->whereIn('type', array_map(fn (CaseType $type): string => $type->value, CaseType::criminal()))
            ->with('stages')
            ->chunkById(200, function (Collection $cases) use (&$count): void {
                foreach ($cases as $case) {
                    $this->refresh($case, reload: false);
                    $count++;
                }
            });

        // Deadlines of cases that are no longer criminal cases.
        CaseDeadline::query()
            ->whereHas('case', fn ($case) => $case->withTrashed()->whereNotIn('type', array_map(fn (CaseType $type): string => $type->value, CaseType::criminal())))
            ->delete();

        return $count;
    }
}
