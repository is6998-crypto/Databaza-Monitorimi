<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Research\HearingResource;
use App\Services\Research\ResearchQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Hearings
 *
 * Anonymized, verified monitored hearings (structured answers only).
 *
 * @authenticated
 */
class HearingController extends ApiController
{
    /**
     * List hearings
     *
     * @queryParam type string Case type. Example: criminal_general
     * @queryParam court_id integer Court id. Example: 1
     * @queryParam from string Hearings on or after this date (Y-m-d). Example: 2024-01-01
     * @queryParam until string Hearings on or before this date (Y-m-d). Example: 2024-12-31
     * @queryParam per_page integer Items per page (max 100). Example: 50
     * @queryParam page integer Page number. Example: 1
     * @queryParam lang string Language of the labels: sq (default) or en. Example: en
     *
     * @response 200 scenario="Success" {"data":[{"ref":"01JD8Z6Y5P4S7T6W2Q9R1M3K5N","case_ref":"01JD8Z6Y5P4S7T6W2Q9R1M3K5A","case_type":"criminal_corruption","court":{"id":1,"label":"Basic Court of Prishtina"},"date":"2024-03-12","hearing_type":"main_trial","venue":"courtroom","is_public":true,"started_late":true,"delay_minutes":25,"delay_causes":["panel"],"was_postponed":true,"postponement_reasons":["defence_lawyer"],"plea_agreement":false,"recording":"audio","interpretation_needed":false,"interpretation_provided":null,"phone_used":false,"phone_users":[],"security_adequate":true,"participants":["judge","prosecutor","accused"]}],"links":{},"meta":{"current_page":1,"per_page":50,"total":700}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->filters($request, ['type', 'court_id', 'from', 'until']);
        $hearings = ResearchQueries::hearings($filters)->paginate($this->perPage($request))->withQueryString();

        $this->logExport($request, 'hearings', $hearings->count(), [...$filters, 'page' => $hearings->currentPage()]);

        return HearingResource::collection($hearings);
    }
}
