<?php

namespace App\Http\Resources\Research;

use App\Models\Hearing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Anonymized hearing: structured answers only (no notes, comments or people).
 *
 * @mixin Hearing
 */
class HearingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Hearing $hearing */
        $hearing = $this->resource;

        return [
            'ref' => $hearing->public_ref,
            'case_ref' => $hearing->case->public_ref,
            'case_type' => $hearing->case->type->value,
            'court' => $hearing->court ? ['id' => $hearing->court->id, 'label' => $hearing->court->label()] : null,
            'date' => $hearing->hearing_date->toDateString(),
            'hearing_type' => $hearing->hearing_type?->value,
            'venue' => $hearing->venue?->value,
            'is_public' => $hearing->is_public,
            'started_late' => $hearing->started_late,
            'delay_minutes' => $hearing->delay_minutes,
            'delay_cause' => $hearing->delay_cause?->value,
            'was_postponed' => $hearing->was_postponed,
            'postponement_reasons' => $hearing->postponement_reasons ?? [],
            'recording' => $hearing->recording?->value,
            'interpretation_needed' => $hearing->interpretation_needed,
            'interpretation_provided' => $hearing->interpretation_provided,
            'phone_used' => $hearing->phone_used,
            'phone_users' => $hearing->phone_users ?? [],
            'security_adequate' => $hearing->security_adequate,
            'participants' => $hearing->participants ?? [],
        ];
    }
}
