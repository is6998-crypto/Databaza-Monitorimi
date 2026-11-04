<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CaseType;
use App\Enums\DelayCause;
use App\Enums\HearingType;
use App\Enums\Participant;
use App\Enums\PartyRole;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Models\Reference\Court;
use App\Models\Reference\CriminalOffence;
use Illuminate\Http\JsonResponse;

/**
 * @group Reference data
 *
 * Code lists used by the other endpoints (no personal data: judges and prosecutors are not exposed).
 *
 * @authenticated
 */
class ReferenceController extends ApiController
{
    /**
     * Courts
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"code":"GJTH-PR","label":"Basic Court of Prishtina","level":"basic","city":"Prishtina"}]}
     */
    public function courts(): JsonResponse
    {
        return response()->json(['data' => Court::query()->orderBy('id')->get()->map(fn (Court $court): array => [
            'id' => $court->id,
            'code' => $court->code,
            'label' => $court->label(),
            'level' => $court->level->value,
            'city' => $court->city,
        ])]);
    }

    /**
     * Criminal offences
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"code":"KPRK-2019","article":"414","title":"Abuse of official position or authority","category":"Corruption"}]}
     */
    public function offences(): JsonResponse
    {
        return response()->json(['data' => CriminalOffence::query()->with(['criminalCode', 'category'])->orderBy('id')->get()->map(fn (CriminalOffence $offence): array => [
            'id' => $offence->id,
            'code' => $offence->criminalCode?->code,
            'article' => $offence->article_number,
            'title' => $offence->getAttribute('title_'.app()->getLocale()) ?: $offence->title_sq,
            'category' => $offence->category?->label(),
        ])]);
    }

    /**
     * Code lists (enumerations)
     *
     * @response 200 scenario="Success" {"case_types":{"criminal_corruption":"Criminal – Corruption"},"stage_types":{"criminal_report_filed":"Criminal report filed"}}
     */
    public function codes(): JsonResponse
    {
        return response()->json([
            'case_types' => CaseType::options(),
            'stage_types' => StageType::options(),
            'stage_outcomes' => StageOutcome::options(),
            'party_roles' => PartyRole::options(),
            'hearing_types' => HearingType::options(),
            'postponement_reasons' => PostponementReason::options(),
            'delay_causes' => DelayCause::options(),
            'participants' => Participant::options(),
        ]);
    }
}
