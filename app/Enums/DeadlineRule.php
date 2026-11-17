<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Phases of the criminal procedure that have a time limit in the Criminal Procedure Code. Each phase runs from
 * one case stage to another; the numbers are in config/deadlines.php.
 *
 * The phase of objections to evidence and requests to dismiss the indictment has no limit in the Code, so it has
 * no rule here.
 */
enum DeadlineRule: string implements HasLabel
{
    use HasTranslatedLabel;

    case Investigation = 'investigation';
    case IndictmentFiling = 'indictment_filing';
    case InitialHearing = 'initial_hearing';
    case MainTrialScheduling = 'main_trial_scheduling';
    case MainTrial = 'main_trial';
    case JudgmentAnnouncement = 'judgment_announcement';
    case JudgmentDrafting = 'judgment_drafting';
    case AppealDecision = 'appeal_decision';

    /**
     * Stages the limit is counted from; the first one that has a date is used.
     *
     * @return list<StageType>
     */
    public function startStages(): array
    {
        return match ($this) {
            self::Investigation => [StageType::InvestigationOpened],
            self::IndictmentFiling => [StageType::InvestigationClosed],
            self::InitialHearing => [StageType::IndictmentFiled],
            self::MainTrialScheduling => [StageType::IndictmentDecision],
            self::MainTrial => [StageType::MainHearing],
            self::JudgmentAnnouncement => [StageType::MainHearingConcluded],
            self::JudgmentDrafting => [StageType::JudgmentAnnounced],
            // Counted from the day the Court of Appeals received the file; the day of the appeal when that is unknown.
            self::AppealDecision => [StageType::AppealFilesReceived, StageType::AppealFiled],
        };
    }

    /**
     * Stages that end the phase; the first one that has a date is used.
     *
     * @return list<StageType>
     */
    public function endStages(): array
    {
        return match ($this) {
            self::Investigation => [StageType::InvestigationClosed, StageType::IndictmentFiled],
            self::IndictmentFiling => [StageType::IndictmentFiled],
            self::InitialHearing => [StageType::InitialHearing],
            self::MainTrialScheduling => [StageType::MainHearing],
            self::MainTrial => [StageType::MainHearingConcluded, StageType::JudgmentAnnounced],
            self::JudgmentAnnouncement => [StageType::JudgmentAnnounced],
            self::JudgmentDrafting => [StageType::JudgmentDrafted],
            self::AppealDecision => [StageType::AppealDecision],
        };
    }

    /**
     * Article of the Criminal Procedure Code.
     */
    public function article(): string
    {
        return (string) config("deadlines.rules.{$this->value}.article");
    }

    /**
     * The limit for one basis ("standard", "detention", "single_judge", "panel", "extended").
     *
     * @return array{0: int, 1: string}|null [amount, unit]
     */
    public function limit(string $basis): ?array
    {
        $limit = config("deadlines.rules.{$this->value}.{$basis}");

        return is_array($limit) && count($limit) === 2 ? [(int) $limit[0], (string) $limit[1]] : null;
    }
}
