<?php

namespace App\Services\Portal;

use App\Enums\ChargeStage;
use App\Enums\StageType;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Hearing;

/**
 * Anonymized public view of a verified case: no names, no case numbers, no judges or
 * prosecutors, and dates reduced to month and year.
 */
final class PublicCasePresenter
{
    /**
     * Relations needed by summary()/detail().
     *
     * @return array<int|string, mixed>
     */
    public static function relations(): array
    {
        return [
            'court', 'department', 'stages',
            'charges.offence.criminalCode',
            'hearings' => fn ($query) => $query->verified(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(CourtCase $case): array
    {
        /** @var CaseStage|null $latest */
        $latest = $case->stages->sortBy(fn (CaseStage $stage): int => $stage->stage->order())->last();
        $judgment = $case->stages->first(fn (CaseStage $stage): bool => $stage->stage === StageType::JudgmentAnnounced);
        $first = $case->stages->whereNotNull('date')->sortBy('date')->first();

        return [
            'ref' => $case->public_code,
            'public_ref' => $case->public_ref,
            'type' => $case->type->getLabel(),
            'court' => $case->court?->label(),
            'department' => $case->department?->label(),
            'year' => (int) ($first?->date?->format('Y') ?? $case->created_at?->format('Y')),
            'offences' => self::offences($case),
            'stage' => $latest?->stage->getLabel(),
            'outcome' => $judgment?->outcome?->getLabel(),
            'hearings' => $case->hearings->count(),
            'postponed' => $case->hearings->where('was_postponed', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(CourtCase $case): array
    {
        return [
            ...self::summary($case),
            'timeline' => $case->stages
                ->sortBy(fn (CaseStage $stage): int => $stage->stage->order())
                ->map(fn (CaseStage $stage): array => [
                    'stage' => $stage->stage->getLabel(),
                    'date' => $stage->date?->format('m.Y'),
                    'outcome' => $stage->outcome?->getLabel(),
                ])
                ->values()
                ->all(),
            'hearing_summary' => [
                'total' => $case->hearings->count(),
                'postponed' => $case->hearings->where('was_postponed', true)->count(),
                'late' => $case->hearings->where('started_late', true)->count(),
                'public' => $case->hearings->where('is_public', true)->count(),
                'first' => $case->hearings->min(fn (Hearing $hearing) => $hearing->hearing_date)?->format('m.Y'),
                'last' => $case->hearings->max(fn (Hearing $hearing) => $hearing->hearing_date)?->format('m.Y'),
            ],
        ];
    }

    /**
     * Offence titles (most advanced charge stage first), without parties.
     *
     * @return list<string>
     */
    public static function offences(CourtCase $case): array
    {
        $order = [ChargeStage::Judgment->value => 0, ChargeStage::Indictment->value => 1, ChargeStage::CriminalReport->value => 2];

        return $case->charges
            ->sortBy(fn (Charge $charge): int => $order[$charge->stage->value] ?? 3)
            ->map(fn (Charge $charge): string => $charge->offence->label())
            ->unique()
            ->values()
            ->all();
    }
}
