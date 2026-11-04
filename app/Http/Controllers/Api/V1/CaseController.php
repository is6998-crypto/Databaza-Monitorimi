<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Research\CaseResource;
use App\Services\Research\ResearchQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Cases
 *
 * Anonymized, verified (approved or published) monitored cases.
 *
 * @authenticated
 */
class CaseController extends ApiController
{
    /**
     * List cases
     *
     * @queryParam type string Case type (e.g. criminal_corruption, civil). Example: criminal_corruption
     * @queryParam court_id integer Court id (see /reference/courts). Example: 1
     * @queryParam year integer Year in which any stage took place. Example: 2024
     * @queryParam per_page integer Items per page (max 100). Example: 25
     * @queryParam page integer Page number. Example: 1
     * @queryParam lang string Language of the labels: sq (default) or en. Example: en
     *
     * @response 200 scenario="Success" {"data":[{"ref":"01JD8Z6Y5P4S7T6W2Q9R1M3K5N","code":"CW-Q9R1M3K5N","type":{"value":"criminal_corruption","label":"Criminal – Corruption"},"court":{"id":1,"label":"Basic Court of Prishtina","level":"basic"},"department":"Serious Crimes Department","status":"approved","parties":{"total":3,"accused":2},"stages":[{"stage":"criminal_report_filed","label":"Criminal report filed","date":"2023-01-10","outcome":null,"is_direct_indictment":false,"appellant":null}],"offences":[{"stage":"indictment","code":"KPRK-2019","article":"414","title":"KPRK-2019 art. 414 — Abuse of official position or authority","category":"Corruption"}],"hearings":{"total":5,"postponed":2}}],"links":{"first":"...","last":"...","prev":null,"next":"..."},"meta":{"current_page":1,"per_page":25,"total":120}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->filters($request, ['type', 'court_id', 'year']);
        $cases = ResearchQueries::cases($filters)->paginate($this->perPage($request))->withQueryString();

        $this->logExport($request, 'cases', $cases->count(), [...$filters, 'page' => $cases->currentPage()]);

        return CaseResource::collection($cases);
    }

    /**
     * Show a case
     *
     * @urlParam ref string required The case reference (ULID) from the list. Example: 01JD8Z6Y5P4S7T6W2Q9R1M3K5N
     *
     * @queryParam lang string Language of the labels: sq (default) or en. Example: en
     *
     * @response 200 scenario="Success" {"data":{"ref":"01JD8Z6Y5P4S7T6W2Q9R1M3K5N","code":"CW-Q9R1M3K5N","type":{"value":"criminal_corruption","label":"Criminal – Corruption"},"court":{"id":1,"label":"Basic Court of Prishtina","level":"basic"},"department":"Serious Crimes Department","status":"published","parties":{"total":3,"accused":2},"stages":[{"stage":"indictment_filed","label":"Indictment filed","date":"2023-05-02","outcome":null,"is_direct_indictment":false,"appellant":null}],"offences":[{"stage":"indictment","code":"KPRK-2019","article":"414","title":"KPRK-2019 art. 414 — Abuse of official position or authority","category":"Corruption"}],"hearings":{"total":5,"postponed":2}}}
     * @response 404 scenario="Unknown or unverified case" {"message":"Not Found"}
     */
    public function show(Request $request, string $ref): CaseResource
    {
        $case = ResearchQueries::cases()->where('public_ref', $ref)->firstOrFail();

        $this->logExport($request, 'cases.show', 1, ['ref' => $ref]);

        return new CaseResource($case);
    }
}
