<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Case milestones. Each case has at most one row per stage type (except hearings,
 * which have their own table). Dates must respect the order defined in predecessors().
 */
enum StageType: string implements HasLabel
{
    use HasTranslatedLabel;

    case CriminalReportFiled = 'criminal_report_filed';
    case InvestigationOpened = 'investigation_opened';
    case InvestigationSuspended = 'investigation_suspended';
    case InvestigationClosed = 'investigation_closed';
    case IndictmentDrafted = 'indictment_drafted';
    case IndictmentFiled = 'indictment_filed';
    case InitialHearing = 'initial_hearing';
    case SecondHearing = 'second_hearing';
    case IndictmentDecision = 'indictment_decision';
    case PleaAgreement = 'plea_agreement';
    case MainHearing = 'main_hearing';
    case JudgmentAnnounced = 'judgment_announced';
    case JudgmentDrafted = 'judgment_drafted';
    case AppealFiled = 'appeal_filed';
    case AppealDecision = 'appeal_decision';
    case ProtectionOfLegalityRequested = 'protection_of_legality_requested';
    case SupremeCourtDecision = 'supreme_court_decision';
    case JudgmentExecuted = 'judgment_executed';

    /**
     * Stages that must be dated on or before this one when both exist.
     *
     * @return list<self>
     */
    public function predecessors(): array
    {
        return match ($this) {
            self::CriminalReportFiled => [],
            self::InvestigationOpened => [self::CriminalReportFiled],
            self::InvestigationSuspended => [self::InvestigationOpened],
            self::InvestigationClosed => [self::InvestigationOpened],
            self::IndictmentDrafted => [self::CriminalReportFiled, self::InvestigationOpened],
            self::IndictmentFiled => [self::IndictmentDrafted, self::InvestigationOpened],
            self::InitialHearing => [self::IndictmentFiled],
            self::SecondHearing => [self::InitialHearing],
            self::IndictmentDecision => [self::IndictmentFiled],
            self::PleaAgreement => [self::IndictmentFiled],
            self::MainHearing => [self::InitialHearing, self::SecondHearing],
            self::JudgmentAnnounced => [self::MainHearing, self::InitialHearing],
            self::JudgmentDrafted => [self::JudgmentAnnounced],
            self::AppealFiled => [self::JudgmentAnnounced],
            self::AppealDecision => [self::AppealFiled],
            self::ProtectionOfLegalityRequested => [self::JudgmentAnnounced, self::AppealDecision],
            self::SupremeCourtDecision => [self::ProtectionOfLegalityRequested],
            self::JudgmentExecuted => [self::JudgmentAnnounced, self::AppealDecision],
        };
    }

    /**
     * Transitive closure of predecessors().
     *
     * @return list<self>
     */
    public function allPredecessors(): array
    {
        $seen = [];
        $stack = $this->predecessors();

        while ($stack !== []) {
            $stage = array_pop($stack);

            if (isset($seen[$stage->value])) {
                continue;
            }

            $seen[$stage->value] = $stage;
            array_push($stack, ...$stage->predecessors());
        }

        return array_values($seen);
    }

    /**
     * @return list<StageOutcome>
     */
    public function outcomes(): array
    {
        return match ($this) {
            self::IndictmentDecision => [StageOutcome::Approved, StageOutcome::Rejected, StageOutcome::Dismissed],
            self::PleaAgreement => [StageOutcome::Accepted, StageOutcome::Rejected],
            self::JudgmentAnnounced, self::JudgmentDrafted => [StageOutcome::Convicting, StageOutcome::Acquitting, StageOutcome::Rejecting, StageOutcome::Mixed],
            self::AppealDecision => [StageOutcome::Confirmed, StageOutcome::Modified, StageOutcome::Annulled, StageOutcome::ReturnedForRetrial],
            self::SupremeCourtDecision => [StageOutcome::Approved, StageOutcome::PartiallyApproved, StageOutcome::Rejected],
            default => [],
        };
    }

    public function hasOutcome(): bool
    {
        return $this->outcomes() !== [];
    }

    public function needsApplicant(): bool
    {
        return $this === self::CriminalReportFiled;
    }

    public function needsProsecutor(): bool
    {
        return $this === self::IndictmentDrafted;
    }

    public function canBeDirectIndictment(): bool
    {
        return $this === self::IndictmentFiled;
    }

    public function needsAppellant(): bool
    {
        return in_array($this, [self::AppealFiled, self::ProtectionOfLegalityRequested], true);
    }

    /**
     * Position on the timeline (used for sorting).
     */
    public function order(): int
    {
        return (int) array_search($this, self::cases(), true);
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForOutcome(self $stage): array
    {
        $options = [];

        foreach ($stage->outcomes() as $outcome) {
            $options[$outcome->value] = $outcome->getLabel();
        }

        return $options;
    }
}
