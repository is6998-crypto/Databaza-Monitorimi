<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Research\PartyResource;
use App\Services\Research\ResearchQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Parties
 *
 * Anonymized parties of verified cases: pseudonymous id, role, gender, nationality,
 * economic status, position held, counsel, charges and sentences. Never names.
 *
 * @authenticated
 */
class PartyController extends ApiController
{
    /**
     * List parties
     *
     * @queryParam type string Case type. Example: criminal_corruption
     * @queryParam court_id integer Court id. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 50
     * @queryParam page integer Page number. Example: 1
     * @queryParam lang string Language of the labels: sq (default) or en. Example: en
     *
     * @response 200 scenario="Success" {"data":[{"id":"P-3f9a1c0b2e4d","case_ref":"01JD8Z6Y5P4S7T6W2Q9R1M3K5N","case_type":"criminal_corruption","role":"accused","gender":"male","nationality":"Albanian","economic_status":"Employed","position_held":"Mayor","counsel_type":"retained","counsel_at_police":true,"charges":[{"stage":"indictment","code":"KPRK-2019","article":"414","category":"Official corruption and criminal offences against official duty"}],"sentences":[{"kind":"imposed","instance":"first_instance","prison_months":24,"suspended_months":0,"fine_eur":1000}]}],"links":{},"meta":{"current_page":1,"per_page":50,"total":300}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->filters($request, ['type', 'court_id']);
        $parties = ResearchQueries::parties($filters)->paginate($this->perPage($request))->withQueryString();

        $this->logExport($request, 'parties', $parties->count(), [...$filters, 'page' => $parties->currentPage()]);

        return PartyResource::collection($parties);
    }
}
