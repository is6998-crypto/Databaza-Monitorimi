<?php

namespace App\Services\Deadlines;

use App\Enums\DeadlineRule;
use App\Enums\DeadlineState;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\TrialBench;
use App\Models\CaseStage;
use App\Models\CourtCase;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Works out, from the dated stages of a criminal case, every phase that has a legal time limit: when it started,
 * which limit applies and whether the phase is still running.
 *
 * It only states facts that do not depend on today's date; whether a running phase is already over its limit is
 * decided where it is shown and by the daily check (App\Models\CaseDeadline).
 */
class DeadlineCalculator
{
    /**
     * @return array<string, array{rule: DeadlineRule, state: DeadlineState, basis: string, limit_amount: int, limit_unit: string, start_stage: StageType, started_on: CarbonInterface, due_on: CarbonInterface, extended_due_on: CarbonInterface|null, ended_on: CarbonInterface|null}> by rule value
     */
    public function forCase(CourtCase $case): array
    {
        if (! $case->type->isCriminal()) {
            return [];
        }

        /** @var array<string, CaseStage> $stages dated stages by stage value */
        $stages = [];

        foreach ($case->stages as $stage) {
            if ($stage->date !== null) {
                $stages[$stage->stage->value] = $stage;
            }
        }

        $deadlines = [];

        foreach (DeadlineRule::cases() as $rule) {
            $start = $this->firstDated($rule->startStages(), $stages);

            if ($start === null || ! $this->applies($rule, $case, $stages)) {
                continue;
            }

            [$basis, $limit] = $this->limitFor($rule, $case);

            if ($limit === null) {
                continue;
            }

            /** @var Carbon $startedOn */
            $startedOn = $start->date;
            $dueOn = $this->add($startedOn, $limit);
            $extended = $basis === 'extended' ? null : $rule->limit('extended');
            $end = $this->firstDated($rule->endStages(), $stages);

            $deadlines[$rule->value] = [
                'rule' => $rule,
                'state' => $this->state($rule, $stages, $end, $dueOn),
                'basis' => $basis,
                'limit_amount' => $limit[0],
                'limit_unit' => $limit[1],
                'start_stage' => $start->stage,
                'started_on' => $startedOn,
                'due_on' => $dueOn,
                'extended_due_on' => $extended !== null ? $this->add($startedOn, $extended) : null,
                'ended_on' => $end?->date,
            ];
        }

        return $deadlines;
    }

    /**
     * @param  list<StageType>  $candidates
     * @param  array<string, CaseStage>  $stages
     */
    private function firstDated(array $candidates, array $stages): ?CaseStage
    {
        foreach ($candidates as $candidate) {
            if (isset($stages[$candidate->value])) {
                return $stages[$candidate->value];
            }
        }

        return null;
    }

    /**
     * @param  array<string, CaseStage>  $stages
     */
    private function applies(DeadlineRule $rule, CourtCase $case, array $stages): bool
    {
        return match ($rule) {
            // The limit for the Court of Appeals exists only while the accused is detained.
            DeadlineRule::AppealDecision => $case->in_detention === true,
            // A main trial is scheduled only if the indictment stands and no guilty-plea agreement ended the case.
            DeadlineRule::MainTrialScheduling => ! in_array($stages[StageType::IndictmentDecision->value]->outcome ?? null, [StageOutcome::Rejected, StageOutcome::Dismissed], true)
                && ($stages[StageType::PleaAgreement->value]->outcome ?? null) !== StageOutcome::Accepted,
            default => true,
        };
    }

    /**
     * @return array{0: string, 1: array{0: int, 1: string}|null} [basis, limit]
     */
    private function limitFor(DeadlineRule $rule, CourtCase $case): array
    {
        $detained = $case->in_detention === true;

        $basis = match ($rule) {
            DeadlineRule::Investigation => $case->investigation_extended ? 'extended' : 'standard',
            DeadlineRule::InitialHearing => $detained ? 'detention' : 'standard',
            DeadlineRule::MainTrial => match ($case->trial_bench) {
                TrialBench::SingleJudge => 'single_judge',
                TrialBench::Panel => 'panel',
                // Not recorded: the longer limit, so that nobody is warned too early.
                default => 'bench_unknown',
            },
            DeadlineRule::JudgmentDrafting => match (true) {
                (bool) $case->is_complex => 'extended',
                $detained => 'detention',
                default => 'standard',
            },
            DeadlineRule::AppealDecision => 'detention',
            default => 'standard',
        };

        return [$basis, $rule->limit($basis === 'bench_unknown' ? 'panel' : $basis)];
    }

    /**
     * @param  array<string, CaseStage>  $stages
     */
    private function state(DeadlineRule $rule, array $stages, ?CaseStage $end, CarbonInterface $dueOn): DeadlineState
    {
        if ($end !== null && $end->date !== null) {
            return $end->date->lte($dueOn) ? DeadlineState::Met : DeadlineState::Late;
        }

        // A later stage is recorded, so this phase is over even though its own end date was never entered.
        $endOrder = $rule->endStages()[0]->order();

        foreach ($stages as $stage) {
            if ($stage->stage->order() > $endOrder) {
                return DeadlineState::Closed;
            }
        }

        if ($rule === DeadlineRule::Investigation && isset($stages[StageType::InvestigationSuspended->value])) {
            return DeadlineState::Paused;
        }

        return DeadlineState::Open;
    }

    /**
     * @param  array{0: int, 1: string}  $limit
     */
    private function add(CarbonInterface $date, array $limit): CarbonInterface
    {
        [$amount, $unit] = $limit;
        $date = Carbon::parse($date->toDateString());

        return match ($unit) {
            'years' => $date->addYearsNoOverflow($amount),
            'months' => $date->addMonthsNoOverflow($amount),
            default => $date->addDays($amount),
        };
    }
}
