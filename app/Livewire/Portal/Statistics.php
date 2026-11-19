<?php

namespace App\Livewire\Portal;

use App\Enums\CaseType;
use App\Livewire\Portal\Concerns\KeepsPortalLocale;
use App\Models\Reference\Court;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Portal\PortalOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The public overview of the monitored cases: how many, and by year, court, case type and offence category.
 * Filtered by year, court and case type; counts under 5 are suppressed.
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

        $overview = PortalOverview::for($filters);

        return view('livewire.portal.statistics', [
            'headline' => $overview->headline(),
            'byYear' => $overview->casesByYear(),
            'byCourt' => $overview->casesByCourt(),
            'byType' => $overview->casesByType(),
            'byCategory' => $overview->casesByCategory(),
            'selectedYear' => $year !== null ? (string) $year : null,
            'years' => array_keys(AnalyticsService::for(new AnalyticsFilters)->hearingsByYear()),
            'courts' => Court::selectOptions(),
            'types' => CaseType::options(),
        ]);
    }
}
