<?php

namespace App\Services\Export;

use App\Enums\DelayCause;
use App\Enums\Participant;
use App\Enums\PhoneUser;
use App\Enums\PostponementReason;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use Closure;

/**
 * Staff export columns. Party names are never exported; parties are counted only.
 */
final class ExportColumns
{
    /**
     * @return array<string, Closure(CourtCase): mixed>
     */
    public static function cases(): array
    {
        return [
            __('cases.fields.public_code') => fn (CourtCase $case) => $case->public_code,
            __('cases.fields.court_case_number') => fn (CourtCase $case) => $case->court_case_number,
            __('cases.fields.indictment_number') => fn (CourtCase $case) => $case->indictment_number,
            __('cases.fields.police_case_number') => fn (CourtCase $case) => $case->police_case_number,
            __('cases.fields.type') => fn (CourtCase $case) => $case->type,
            __('cases.fields.status') => fn (CourtCase $case) => $case->status,
            __('cases.fields.court') => fn (CourtCase $case) => $case->court?->label(),
            __('cases.fields.court_branch') => fn (CourtCase $case) => $case->courtBranch?->label(),
            __('cases.fields.department') => fn (CourtCase $case) => $case->department?->label(),
            __('cases.fields.judge') => fn (CourtCase $case) => $case->judge?->name,
            __('cases.fields.prosecution_office') => fn (CourtCase $case) => $case->prosecutionOffice?->label(),
            __('cases.fields.prosecutor') => fn (CourtCase $case) => $case->prosecutor?->name,
            __('cases.fields.assigned_monitor') => fn (CourtCase $case) => $case->assignedMonitor?->label(),
            __('exports.columns.parties') => fn (CourtCase $case) => $case->parties->count(),
            __('exports.columns.accused') => fn (CourtCase $case) => $case->parties->filter(fn ($party) => in_array($party->role->value, ['accused', 'legal_entity'], true))->count(),
            __('cases.fields.hearings_count') => fn (CourtCase $case) => $case->hearings->count(),
            __('cases.fields.latest_stage') => function (CourtCase $case): ?string {
                /** @var CaseStage|null $stage */
                $stage = $case->stages->sortBy(fn (CaseStage $stage): int => $stage->stage->order())->last();

                return $stage?->stage->getLabel();
            },
            __('exports.columns.latest_stage_date') => fn (CourtCase $case) => $case->stages->sortBy(fn (CaseStage $stage): int => $stage->stage->order())->last()?->date,
            __('cases.fields.created_at') => fn (CourtCase $case) => $case->created_at,
            __('cases.fields.updated_at') => fn (CourtCase $case) => $case->updated_at,
        ];
    }

    /**
     * @return list<string>
     */
    public static function caseRelations(): array
    {
        return ['court', 'courtBranch', 'department', 'judge', 'prosecutionOffice', 'prosecutor', 'assignedMonitor.user', 'parties', 'hearings', 'stages'];
    }

    /**
     * @return array<string, Closure(Hearing): mixed>
     */
    public static function hearings(): array
    {
        $labels = fn (string $enum, ?array $values): ?string => $values
            ? collect($values)->map(fn (string $value): string => (string) ($enum::tryFrom($value)?->getLabel() ?? $value))->implode(', ')
            : null;

        return [
            __('exports.columns.hearing_ref') => fn (Hearing $hearing) => 'H-'.strtoupper(substr($hearing->public_ref, -10)),
            __('cases.fields.court_case_number') => fn (Hearing $hearing) => $hearing->case->court_case_number,
            __('cases.fields.type') => fn (Hearing $hearing) => $hearing->case->type,
            __('hearings.fields.court') => fn (Hearing $hearing) => $hearing->court?->label(),
            __('hearings.fields.judge') => fn (Hearing $hearing) => $hearing->judge?->name,
            __('hearings.fields.prosecutor') => fn (Hearing $hearing) => $hearing->prosecutor?->name,
            __('hearings.fields.monitor') => fn (Hearing $hearing) => $hearing->monitor?->label(),
            __('hearings.fields.hearing_date') => fn (Hearing $hearing) => $hearing->hearing_date,
            __('hearings.fields.hearing_type') => fn (Hearing $hearing) => $hearing->hearing_type,
            __('hearings.fields.venue') => fn (Hearing $hearing) => $hearing->venue,
            __('hearings.fields.is_public') => fn (Hearing $hearing) => $hearing->is_public,
            __('hearings.fields.started_late') => fn (Hearing $hearing) => $hearing->started_late,
            __('hearings.fields.delay_minutes') => fn (Hearing $hearing) => $hearing->delay_minutes,
            __('hearings.fields.delay_causes') => fn (Hearing $hearing) => $labels(DelayCause::class, $hearing->delay_causes),
            __('hearings.fields.plea_agreement') => fn (Hearing $hearing) => $hearing->plea_agreement === null ? null : ($hearing->plea_agreement ? __('app.yes') : __('app.no')),
            __('hearings.fields.was_postponed') => fn (Hearing $hearing) => $hearing->was_postponed,
            __('hearings.fields.postponement_reasons') => fn (Hearing $hearing) => $labels(PostponementReason::class, $hearing->postponement_reasons),
            __('hearings.fields.recording') => fn (Hearing $hearing) => $hearing->recording,
            __('hearings.fields.interpretation_needed') => fn (Hearing $hearing) => $hearing->interpretation_needed,
            __('hearings.fields.interpretation_provided') => fn (Hearing $hearing) => $hearing->interpretation_provided,
            __('hearings.fields.phone_used') => fn (Hearing $hearing) => $hearing->phone_used,
            __('hearings.fields.phone_users') => fn (Hearing $hearing) => $labels(PhoneUser::class, $hearing->phone_users),
            __('hearings.fields.security_adequate') => fn (Hearing $hearing) => $hearing->security_adequate,
            __('hearings.fields.participants') => fn (Hearing $hearing) => $labels(Participant::class, $hearing->participants),
            __('cases.fields.status') => fn (Hearing $hearing) => $hearing->status,
        ];
    }

    /**
     * @return list<string>
     */
    public static function hearingRelations(): array
    {
        return ['case', 'court', 'judge', 'prosecutor', 'monitor.user'];
    }
}
