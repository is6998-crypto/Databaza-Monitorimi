<?php

namespace App\Livewire\Portal;

use App\Enums\CaseType;
use App\Livewire\Portal\Concerns\KeepsPortalLocale;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Portal\PortalStatistics;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Interactive public charts by year, court and case type (suppressed below 5).
 */
class Statistics extends Component
{
    use KeepsPortalLocale;

    #[Url]
    public ?string $year = null;

    #[Url]
    public ?string $court = null;

    #[Url]
    public ?string $type = null;

    public function resetFilters(): void
    {
        $this->reset(['year', 'court', 'type']);
    }

    public function render(): View
    {
        $year = is_numeric($this->year) ? (int) $this->year : null;

        $filters = new AnalyticsFilters(
            from: $year ? Carbon::create($year, 1, 1) : null,
            until: $year ? Carbon::create($year, 12, 31) : null,
            courtId: is_numeric($this->court) ? (int) $this->court : null,
            caseType: CaseType::tryFrom((string) $this->type),
        );

        $statistics = PortalStatistics::for($filters);

        return view('livewire.portal.statistics', [
            'headline' => $statistics->headline(),
            'byYear' => $statistics->hearingsByYear(),
            'byType' => $statistics->casesByType(),
            'byCategory' => $statistics->casesByCategory(),
            'byCourt' => $statistics->postponementByCourt(),
            'reasons' => $statistics->postponementReasons(),
            'verdicts' => $statistics->verdicts(),
            'durations' => $statistics->stageDurations(),
            'years' => array_keys(AnalyticsService::for(new AnalyticsFilters)->hearingsByYear()),
            'courts' => Court::selectOptions(),
            'types' => CaseType::options(),
        ]);
    }
}
