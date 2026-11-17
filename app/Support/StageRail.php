<?php

namespace App\Support;

use App\Enums\DeadlineRule;
use App\Enums\StageType;
use App\Models\CaseDeadline;
use App\Models\CaseStage;
use App\Models\CourtCase;

/**
 * The path of a criminal case as eight steps, from the criminal report to the execution of the judgment, for the
 * rail at the top of the case page: which steps are behind it, where it stands now, and where a legal time
 * limit is exceeded.
 */
final class StageRail
{
    /**
     * Step => [stages that belong to it, limits that are counted inside it].
     *
     * @return array<string, array{0: list<StageType>, 1: list<DeadlineRule>}>
     */
    private static function steps(): array
    {
        return [
            'report' => [[StageType::CriminalReportFiled], []],
            'investigation' => [[StageType::InvestigationOpened, StageType::InvestigationSuspended, StageType::InvestigationClosed], [DeadlineRule::Investigation]],
            'indictment' => [[StageType::IndictmentDrafted, StageType::IndictmentFiled], [DeadlineRule::IndictmentFiling]],
            'initial' => [[StageType::InitialHearing, StageType::SecondHearing, StageType::IndictmentDecision, StageType::PleaAgreement], [DeadlineRule::InitialHearing]],
            'trial' => [[StageType::MainHearing, StageType::MainHearingConcluded], [DeadlineRule::MainTrialScheduling, DeadlineRule::MainTrial]],
            'judgment' => [[StageType::JudgmentAnnounced, StageType::JudgmentDrafted], [DeadlineRule::JudgmentAnnouncement, DeadlineRule::JudgmentDrafting]],
            'appeal' => [[StageType::AppealFiled, StageType::AppealFilesReceived, StageType::AppealDecision, StageType::ProtectionOfLegalityRequested, StageType::SupremeCourtDecision], [DeadlineRule::AppealDecision]],
            'execution' => [[StageType::JudgmentExecuted], []],
        ];
    }

    /**
     * @return list<array{key: string, label: string, state: string, date: string|null, detail: string|null, overdue: string|null}>
     */
    public static function for(CourtCase $case): array
    {
        $format = (string) config('courtwatch.date_format');
        $rail = [];
        $current = null;

        foreach (self::steps() as $key => [$stages, $rules]) {
            /** @var CaseStage|null $latest the latest dated stage of this step */
            $latest = $case->stages
                ->filter(fn (CaseStage $stage): bool => $stage->date !== null && in_array($stage->stage, $stages, true))
                ->sortBy(fn (CaseStage $stage): int => $stage->stage->order())
                ->last();

            /** @var CaseDeadline|null $overdue */
            $overdue = $case->deadlines
                ->first(fn (CaseDeadline $deadline): bool => in_array($deadline->rule, $rules, true) && $deadline->isOverdue());

            if ($latest !== null) {
                $current = $key;
            }

            $rail[] = [
                'key' => $key,
                'label' => (string) __("cases.rail.{$key}"),
                'state' => $latest !== null ? 'done' : 'todo',
                'date' => $latest?->date?->format($format),
                'detail' => $latest?->stage->getLabel(),
                'overdue' => $overdue !== null
                    ? '+'.trans_choice('deadlines.units.days', $overdue->daysOver(), ['count' => $overdue->daysOver()])
                    : null,
            ];
        }

        // The last step with a date is where the case stands; an empty step before it was skipped.
        $reached = false;

        foreach (array_reverse(array_keys($rail)) as $index) {
            if ($rail[$index]['key'] === $current) {
                $rail[$index]['state'] = 'current';
                $reached = true;
            } elseif ($reached && $rail[$index]['state'] === 'todo') {
                $rail[$index]['state'] = 'skipped';
            }
        }

        return $rail;
    }
}
