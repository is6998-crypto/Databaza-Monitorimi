<?php

namespace App\Services\Export;

use App\Enums\StageType;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\Sentence;
use App\Support\Pseudonym;
use Closure;

/**
 * Anonymized record-level datasets for approved researchers: pseudonymous ids, categories,
 * dates and structured answers. No names, case numbers, judges, prosecutors, monitors or
 * free-text fields.
 */
final class ResearchExportColumns
{
    /**
     * @return array<string, Closure(CourtCase): mixed>
     */
    public static function cases(): array
    {
        $stageDate = fn (StageType $type): Closure => fn (CourtCase $case) => $case->stages->first(fn (CaseStage $stage): bool => $stage->stage === $type)?->date;
        $stageOutcome = fn (StageType $type): Closure => fn (CourtCase $case) => $case->stages->first(fn (CaseStage $stage): bool => $stage->stage === $type)?->outcome?->value;

        return [
            'case_ref' => fn (CourtCase $case) => $case->public_ref,
            'case_type' => fn (CourtCase $case) => $case->type->value,
            'court' => fn (CourtCase $case) => $case->court?->label(),
            'court_level' => fn (CourtCase $case) => $case->court?->level->value,
            'department' => fn (CourtCase $case) => $case->department?->label(),
            'parties' => fn (CourtCase $case) => $case->parties->count(),
            'accused' => fn (CourtCase $case) => $case->parties->filter(fn (CaseParty $party): bool => in_array($party->role->value, ['accused', 'legal_entity'], true))->count(),
            'offence_articles' => fn (CourtCase $case) => $case->charges->map(fn (Charge $charge): string => trim(($charge->offence->criminalCode->code ?? '').' '.$charge->offence->article_number))->unique()->implode('; '),
            'criminal_report_date' => $stageDate(StageType::CriminalReportFiled),
            'investigation_opened_date' => $stageDate(StageType::InvestigationOpened),
            'indictment_filed_date' => $stageDate(StageType::IndictmentFiled),
            'initial_hearing_date' => $stageDate(StageType::InitialHearing),
            'indictment_decision' => $stageOutcome(StageType::IndictmentDecision),
            'plea_agreement' => $stageOutcome(StageType::PleaAgreement),
            'judgment_date' => $stageDate(StageType::JudgmentAnnounced),
            'judgment_outcome' => $stageOutcome(StageType::JudgmentAnnounced),
            'appeal_filed_date' => $stageDate(StageType::AppealFiled),
            'appeal_decision' => $stageOutcome(StageType::AppealDecision),
            'appeal_decision_date' => $stageDate(StageType::AppealDecision),
            'supreme_court_decision' => $stageOutcome(StageType::SupremeCourtDecision),
            'hearings_monitored' => fn (CourtCase $case) => $case->hearings->count(),
            'hearings_postponed' => fn (CourtCase $case) => $case->hearings->where('was_postponed', true)->count(),
        ];
    }

    /**
     * @return array<string, Closure(CaseParty): mixed>
     */
    public static function parties(): array
    {
        $sentence = fn (string $kind, string $field): Closure => fn (CaseParty $party) => $party->sentences
            ->first(fn (Sentence $sentence): bool => $sentence->kind->value === $kind && $sentence->instance->value === 'first_instance')?->{$field};

        return [
            'party_id' => fn (CaseParty $party) => Pseudonym::for('party', $party->id),
            'case_ref' => fn (CaseParty $party) => $party->case->public_ref,
            'case_type' => fn (CaseParty $party) => $party->case->type->value,
            'role' => fn (CaseParty $party) => $party->role->value,
            'gender' => fn (CaseParty $party) => $party->gender?->value,
            'nationality' => fn (CaseParty $party) => $party->nationality?->label('en'),
            'economic_status' => fn (CaseParty $party) => $party->economicStatus?->label('en'),
            'position_held' => fn (CaseParty $party) => $party->defendantPosition?->label('en'),
            'counsel_type' => fn (CaseParty $party) => $party->counsel_type?->value,
            'counsel_at_police' => fn (CaseParty $party) => $party->counsel_at_police,
            'charged_articles' => fn (CaseParty $party) => $party->charges->map(fn (Charge $charge): string => $charge->stage->value.':'.trim(($charge->offence->criminalCode->code ?? '').' '.$charge->offence->article_number))->implode('; '),
            'requested_prison_months' => $sentence('requested', 'prison_months'),
            'imposed_prison_months' => $sentence('imposed', 'prison_months'),
            'imposed_suspended_months' => $sentence('imposed', 'suspended_months'),
            'imposed_fine_eur' => $sentence('imposed', 'fine_eur'),
        ];
    }

    /**
     * @return array<string, Closure(Hearing): mixed>
     */
    public static function hearings(): array
    {
        return [
            'hearing_ref' => fn (Hearing $hearing) => $hearing->public_ref,
            'case_ref' => fn (Hearing $hearing) => $hearing->case->public_ref,
            'case_type' => fn (Hearing $hearing) => $hearing->case->type->value,
            'court' => fn (Hearing $hearing) => $hearing->court?->label('en'),
            'date' => fn (Hearing $hearing) => $hearing->hearing_date,
            'hearing_type' => fn (Hearing $hearing) => $hearing->hearing_type?->value,
            'venue' => fn (Hearing $hearing) => $hearing->venue?->value,
            'is_public' => fn (Hearing $hearing) => $hearing->is_public,
            'started_late' => fn (Hearing $hearing) => $hearing->started_late,
            'delay_minutes' => fn (Hearing $hearing) => $hearing->delay_minutes,
            'delay_cause' => fn (Hearing $hearing) => $hearing->delay_cause?->value,
            'was_postponed' => fn (Hearing $hearing) => $hearing->was_postponed,
            'postponement_reasons' => fn (Hearing $hearing) => $hearing->postponement_reasons,
            'recording' => fn (Hearing $hearing) => $hearing->recording?->value,
            'interpretation_needed' => fn (Hearing $hearing) => $hearing->interpretation_needed,
            'interpretation_provided' => fn (Hearing $hearing) => $hearing->interpretation_provided,
            'phone_used' => fn (Hearing $hearing) => $hearing->phone_used,
            'phone_users' => fn (Hearing $hearing) => $hearing->phone_users,
            'security_adequate' => fn (Hearing $hearing) => $hearing->security_adequate,
            'participants' => fn (Hearing $hearing) => $hearing->participants,
        ];
    }
}
