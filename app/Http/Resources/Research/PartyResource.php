<?php

namespace App\Http\Resources\Research;

use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\Sentence;
use App\Support\Pseudonym;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Anonymized party: a pseudonymous id and demographic categories only — never the name.
 *
 * @mixin CaseParty
 */
class PartyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CaseParty $party */
        $party = $this->resource;

        return [
            'id' => Pseudonym::for('party', $party->id),
            'case_ref' => $party->case->public_ref,
            'case_type' => $party->case->type->value,
            'role' => $party->role->value,
            'gender' => $party->gender?->value,
            'nationality' => $party->nationality?->label(),
            'employment_status' => $party->economicStatus?->label(),
            'economic_condition' => $party->economic_condition?->value,
            'position_held' => $party->defendantPosition?->label(),
            'counsel_type' => $party->counsel_type?->value,
            'counsel_at_police' => $party->counsel_at_police,
            'charges' => $party->charges->map(fn (Charge $charge): array => [
                'stage' => $charge->stage->value,
                'code' => $charge->offence->criminalCode?->code,
                'article' => $charge->offence->article_number,
                'category' => $charge->offence->category?->label(),
            ])->values(),
            'sentences' => $party->sentences->map(fn (Sentence $sentence): array => [
                'kind' => $sentence->kind->value,
                'instance' => $sentence->instance->value,
                'prison_months' => $sentence->prison_months,
                'suspended_months' => $sentence->suspended_months,
                'fine_eur' => $sentence->fine_eur !== null ? (float) $sentence->fine_eur : null,
            ])->values(),
        ];
    }
}
