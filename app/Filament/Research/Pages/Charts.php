<?php

namespace App\Filament\Research\Pages;

use App\Enums\CaseType;
use App\Enums\ExportFormat;
use App\Models\ExportLog;
use App\Models\Reference\Category;
use App\Models\Reference\Court;
use App\Models\User;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Export\ExportService;
use App\Services\Research\ChartBuilder;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Chart builder for approved researchers: pick what to count, narrow it with filters, split it along one
 * dimension and optionally compare along a second (App\Services\Research\ChartBuilder). The choice lives in the
 * URL, so a chart can be bookmarked or shared with a colleague who has access. The table can be downloaded as
 * CSV; every download is logged.
 */
class Charts extends Page
{
    protected string $view = 'filament.research.charts';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'charts';

    #[Url]
    public string $measure = 'postponed';

    #[Url]
    public string $dimension = 'category';

    #[Url]
    public ?string $compare = null;

    #[Url(as: 'chart')]
    public string $chartType = 'auto';

    #[Url(as: 'from')]
    public ?string $yearFrom = null;

    #[Url(as: 'to')]
    public ?string $yearTo = null;

    #[Url]
    public ?string $court = null;

    #[Url(as: 'type')]
    public ?string $caseType = null;

    #[Url]
    public ?string $category = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isApprovedResearcher();
    }

    public function getTitle(): string
    {
        return __('research.charts.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('research.charts.title');
    }

    public function updated(string $property): void
    {
        // Reasons for postponement only exist for postponed hearings.
        if (in_array($property, ['dimension', 'compare'], true) && in_array('reason', [$this->dimension, $this->compare], true)) {
            $this->measure = 'postponed';
        }

        if ($property === 'measure' && $this->measure !== 'postponed') {
            $this->dimension = $this->dimension === 'reason' ? 'category' : $this->dimension;
            $this->compare = $this->compare === 'reason' ? null : $this->compare;
        }

        if ($this->compare === $this->dimension || $this->compare === '') {
            $this->compare = null;
        }
    }

    public function resetChart(): void
    {
        $this->reset(['measure', 'dimension', 'compare', 'chartType', 'yearFrom', 'yearTo', 'court', 'caseType', 'category']);
    }

    protected function builder(): ChartBuilder
    {
        return ChartBuilder::fromArray([
            'measure' => $this->measure,
            'dimension' => $this->dimension,
            'compare' => $this->compare,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'court_id' => $this->court,
            'case_type' => $this->caseType,
            'category_id' => $this->category,
        ]);
    }

    /**
     * bar, stacked or line: "auto" draws time as lines and everything else as bars.
     *
     * @param  array<string, mixed>  $result
     */
    protected function resolvedChartType(array $result): string
    {
        return match (true) {
            in_array($this->chartType, ['bar', 'line'], true) => $this->chartType,
            $this->chartType === 'stacked' && $result['compare'] !== null && ! $result['rate'] && $result['measure'] !== 'avg_delay' => 'stacked',
            $this->chartType === 'stacked' => 'bar',
            default => in_array($result['dimension'], ChartBuilder::TIME, true) && count($result['labels']) > 1 ? 'line' : 'bar',
        };
    }

    public function download(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User && static::canAccess(), 403);

        $builder = $this->builder();
        $result = $builder->build();

        $header = [__('research.charts.dimensions.'.$result['dimension']), ...array_map(fn (array $series): string => $series['label'], $result['series'])];
        $rows = [];
        foreach ($result['labels'] as $i => $label) {
            $rows[] = [$label, ...array_map(fn (array $series) => $series['values'][$i], $result['series'])];
        }

        app(ExportService::class)->log('research:chart', ExportFormat::Csv, ExportLog::CONTEXT_RESEARCHER, $user, $builder->toArray(), count($rows));

        return response()->streamDownload(function () use ($header, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, $header, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '');
            }
            fclose($out);
        }, 'courtwatch-chart-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $result = $this->builder()->build();

        return [
            'result' => $result,
            'type' => $this->resolvedChartType($result),
            'measures' => collect(ChartBuilder::MEASURES)->mapWithKeys(fn (string $key): array => [$key => __('research.charts.measures.'.$key)])->all(),
            'dimensions' => collect(ChartBuilder::DIMENSIONS)->mapWithKeys(fn (string $key): array => [$key => __('research.charts.dimensions.'.$key)])->all(),
            'years' => array_keys(AnalyticsService::for(new AnalyticsFilters)->hearingsByYear()),
            'courts' => Court::selectOptions(),
            'types' => CaseType::options(),
            'categories' => Category::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn (Category $category): array => [$category->id => $category->code.' · '.$category->label()])->all(),
        ];
    }

    public static function format(int|float|null $value, string $measure): string
    {
        if ($value === null) {
            return '—';
        }

        return match (true) {
            in_array($measure, ChartBuilder::RATES, true) => number_format((float) $value, 1, ',', '.').' %',
            $measure === 'avg_delay' => number_format((float) $value, 1, ',', '.').' '.__('research.charts.minutes'),
            default => number_format((float) $value, 0, ',', '.'),
        };
    }
}
