<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Analytics\AnalyticsFilters;
use App\Services\Portal\PortalStatistics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Statistics
 *
 * Aggregate indicators (the same suppressed figures as the public portal: values under 5 are null).
 *
 * @authenticated
 */
class StatisticsController extends ApiController
{
    /**
     * Aggregate statistics
     *
     * @queryParam from string Start date (Y-m-d). Example: 2024-01-01
     * @queryParam until string End date (Y-m-d). Example: 2024-12-31
     * @queryParam court_id integer Court id. Example: 1
     * @queryParam case_type string Case type. Example: criminal_corruption
     * @queryParam lang string Language of the labels: sq (default) or en. Example: en
     *
     * @response 200 scenario="Success" {"headline":{"cases":156,"hearings":507,"postponement_rate":31,"late_rate":29.4,"public_rate":83,"conviction_rate":55.9},"hearings_by_year":{"2024":99,"2025":119},"cases_by_type":{"Criminal – Corruption":55},"postponement_by_court":[{"court":"Basic Court of Prishtina","hearings":225,"postponed":63,"rate":28}],"postponement_reasons":{"Defence lawyer":61},"verdicts":{"Convicting":33},"stage_durations":[{"label":"Criminal report filed → Indictment filed","median":329,"cases":103}]}
     */
    public function index(Request $request): JsonResponse
    {
        $statistics = PortalStatistics::for(AnalyticsFilters::fromArray($this->filters($request, ['from', 'until', 'court_id', 'case_type'])));

        return response()->json([
            'headline' => $statistics->headline(),
            'hearings_by_year' => $statistics->hearingsByYear(),
            'cases_by_type' => $statistics->casesByType(),
            'postponement_by_court' => $statistics->postponementByCourt(),
            'postponement_reasons' => $statistics->postponementReasons(),
            'verdicts' => $statistics->verdicts(),
            'stage_durations' => $statistics->stageDurations(),
        ]);
    }
}
