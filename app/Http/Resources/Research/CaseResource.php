<?php

namespace App\Http\Resources\Research;

use App\Enums\PartyRole;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CourtCase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Anonymized case for researchers: no names, no case numbers, no judges/prosecutors/monitors,
 * no free-text fields.
 *
 * @mixin CourtCase
 */
class CaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CourtCase $case */
        $case = $this->resource;

        return [
            'ref' => $case->public_ref,
            'code' => $case->public_code,
            'type' => ['value' => $case->type->value, 'label' => $case->type->getLabel()],
            'court' => $case->court ? ['id' => $case->court->id, 'label' => $case->court->label(), 'level' => $case->court->level->value] : null,
            'department' => $case->department?->label(),
            'status' => $case->status->value,
            'parties' => [
                'total' => $case->parties->count(),
                'accused' => $case->parties->filter(fn ($party): bool => in_array($party->role, [PartyRole::Accused, PartyRole::LegalEntity], true))->count(),
            ],
            'stages' => $case->stages
                ->sortBy(fn (CaseStage $stage): int => $stage->stage->order())
                ->map(fn (CaseStage $stage): array => [
                    'stage' => $stage->stage->value,
                    'label' => $stage->stage->getLabel(),
                    'date' => $stage->date?->toDateString(),
                    'outcome' => $stage->outcome?->value,
                    'is_direct_indictment' => $stage->is_direct_indictment,
                    'appellant' => $stage->appellant?->value,
                ])->values(),
            'offences' => $case->charges
                ->map(fn (Charge $charge): array => [
                    'stage' => $charge->stage->value,
                    'code' => $charge->offence->criminalCode?->code,
                    'article' => $charge->offence->article_number,
                    'title' => $charge->offence->label(),
                    'category' => $charge->offence->category?->label(),
                ])
                ->unique(fn (array $offence): string => $offence['stage'].$offence['code'].$offence['article'])
                ->values(),
            'hearings' => [
                'total' => $case->hearings->count(),
                'postponed' => $case->hearings->where('was_postponed', true)->count(),
            ],
        ];
    }
}
