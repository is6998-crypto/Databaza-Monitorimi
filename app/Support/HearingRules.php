<?php

namespace App\Support;

use App\Enums\DelayCause;
use App\Enums\HearingType;
use App\Enums\Participant;
use App\Enums\PhoneUser;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Rules\NotInFuture;
use Illuminate\Validation\Rule;

/**
 * Validation for hearings submitted outside Filament (monitor PWA sync, API, importer).
 * Mirrors the rules of the Filament hearing form.
 */
final class HearingRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'case_id' => ['required', 'integer', 'exists:cases,id'],
            'hearing_date' => ['required', 'date_format:Y-m-d', new NotInFuture],
            'hearing_type' => ['nullable', Rule::enum(HearingType::class)],
            'venue' => ['nullable', Rule::enum(Venue::class)],
            'is_public' => ['nullable', 'boolean'],
            'started_late' => ['required', 'boolean'],
            'delay_minutes' => ['nullable', 'integer', 'min:16', 'max:600'],
            'delay_causes' => ['nullable', 'array'],
            'delay_causes.*' => [Rule::enum(DelayCause::class)],
            'plea_agreement' => ['nullable', 'boolean'],
            'was_postponed' => ['required', 'boolean'],
            'postponement_reasons' => ['nullable', 'array', 'required_if_accepted:was_postponed'],
            'postponement_reasons.*' => [Rule::enum(PostponementReason::class)],
            'recording' => ['nullable', Rule::enum(Recording::class)],
            'interpretation_needed' => ['required', 'boolean'],
            'interpretation_provided' => ['nullable', 'boolean'],
            'phone_used' => ['required', 'boolean'],
            'phone_users' => ['nullable', 'array'],
            'phone_users.*' => [Rule::enum(PhoneUser::class)],
            'security_adequate' => ['nullable', 'boolean'],
            'security_comment' => ['nullable', 'string', 'max:2000'],
            'participants' => ['nullable', 'array'],
            'participants.*' => [Rule::enum(Participant::class)],
            'present_party_ids' => ['nullable', 'array'],
            'present_party_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Hearing attributes from validated input.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function attributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'case_id', 'hearing_date', 'hearing_type', 'venue', 'is_public', 'started_late', 'delay_minutes', 'delay_causes',
            'was_postponed', 'postponement_reasons', 'plea_agreement', 'recording', 'interpretation_needed', 'interpretation_provided',
            'phone_used', 'phone_users', 'security_adequate', 'security_comment', 'participants', 'notes', 'client_uuid',
        ]));
    }
}
