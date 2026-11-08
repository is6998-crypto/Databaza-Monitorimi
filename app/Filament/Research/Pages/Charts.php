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
use App\Services\Anonymizer;
use App\Services\Export\ExportService;
use App\Services\Research\ChartBuilder;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
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
        // Reasons for postponement (and for a late start) only exist for the hearings they explain.
        if (in_array($property, ['dimension', 'compare'], true)) {
            $other = $property === 'dimension' ? 'compare' : 'dimension';
            if (ChartBuilder::onlyMeasure($this->{$property}) !== null && ChartBuilder::onlyMeasure($this->{$other}) !== null) {
                $other === 'compare' ? $this->compare = null : $this->dimension = 'year';
            }
            $this->measure = ChartBuilder::onlyMeasure($this->dimension) ?? ChartBuilder::onlyMeasure($this->compare) ?? $this->measure;
        }

        if ($property === 'measure') {
            $this->dimension = ChartBuilder::onlyMeasure($this->dimension) !== null && ChartBuilder::onlyMeasure($this->dimension) !== $this->measure ? 'category' : $this->dimension;
            $this->compare = ChartBuilder::onlyMeasure($this->compare) !== null && ChartBuilder::onlyMeasure($this->compare) !== $this->measure ? null : $this->compare;
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

    /** The chart types on offer ("auto" picks lines for time and bars for the rest). */
    public const CHART_TYPES = ['auto', 'bar', 'horizontal', 'stacked', 'stacked_percent', 'line', 'area', 'pie', 'doughnut', 'polar', 'radar', 'table'];

    /** Types that only make sense with several series stacked, and types that show one series as slices. */
    public const NEEDS_COMPARE = ['stacked', 'stacked_percent'];

    public const SLICES = ['pie', 'doughnut', 'polar'];

    /**
     * The type actually drawn: stacks need a comparison and counts (not shares or averages); "auto" draws time as
     * lines and everything else as bars.
     *
     * @param  array<string, mixed>  $result
     */
    protected function resolvedChartType(array $result): string
    {
        $stackable = $result['compare'] !== null && $this->isCountMeasure($result['measure']);

        return match (true) {
            in_array($this->chartType, self::NEEDS_COMPARE, true) => $stackable ? $this->chartType : 'bar',
            in_array($this->chartType, self::CHART_TYPES, true) && $this->chartType !== 'auto' => $this->chartType,
            default => in_array($result['dimension'], ChartBuilder::TIME, true) && count($result['labels']) > 1 ? 'line' : 'bar',
        };
    }

    private function isCountMeasure(string $measure): bool
    {
        return ! in_array($measure, [...ChartBuilder::RATES, 'avg_delay', 'avg_accused'], true);
    }

    /**
     * The words that go with a downloaded chart or table: title, what was chosen, the source and the notes.
     *
     * @param  array<string, mixed>  $result
     * @return array{title: string, subtitle: string, filters: string, notes: list<string>, source: string}
     */
    public function caption(array $result): array
    {
        $dimensions = fn (string $key): string => __('research.charts.dimensions.'.$key);
        $view = ['courts' => Court::selectOptions(), 'types' => CaseType::options(), 'categories' => Category::query()->pluck('code', 'id')->all()];

        $filters = array_filter([
            $this->yearFrom || $this->yearTo ? __('research.charts.fields.years').': '.($this->yearFrom ?: '…').'–'.($this->yearTo ?: '…') : null,
            $this->court ? __('research.charts.fields.court').': '.($view['courts'][$this->court] ?? $this->court) : null,
            $this->caseType ? __('research.charts.fields.case_type').': '.($view['types'][$this->caseType] ?? $this->caseType) : null,
            $this->category ? __('research.charts.fields.category').': '.($view['categories'][$this->category] ?? $this->category) : null,
        ]);

        $hasSmall = false;
        foreach ($result['series'] as $series) {
            foreach ($series['bases'] as $base) {
                $hasSmall = $hasSmall || ChartBuilder::isSmall((int) $base);
            }
        }

        return [
            'title' => __('research.charts.measures.'.$result['measure']),
            'subtitle' => __('research.charts.by', ['dimension' => mb_strtolower($dimensions($result['dimension']))]).($result['compare'] ? ' '.__('research.charts.and_by', ['dimension' => mb_strtolower($dimensions($result['compare']))]) : ''),
            'filters' => $filters === [] ? __('research.charts.no_filters') : implode(' · ', $filters),
            'notes' => array_values(array_filter([
                trans_choice('research.charts.based_on_'.$result['unit'], $result['total'], ['count' => number_format($result['total'], 0, ',', '.')]),
                $hasSmall ? '* '.__('research.charts.small_note', ['threshold' => Anonymizer::threshold()]) : null,
                array_intersect(['category', 'offence', 'security_measure'], [$result['dimension'], $result['compare']]) !== [] ? __('research.charts.category_note') : null,
                array_intersect(ChartBuilder::ACCUSED_DIMENSIONS, [$result['dimension'], $result['compare']]) !== [] ? __('research.charts.accused_note') : null,
            ])),
            'source' => __('research.charts.source', ['date' => now('Europe/Belgrade')->format('d.m.Y')]),
        ];
    }

    /**
     * The table as a spreadsheet: CourtWatch and the caption above it, the figures below (small cells marked).
     */
    public function download(string $format = 'csv'): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User && static::canAccess(), 403);

        $format = $format === 'xlsx' ? ExportFormat::Xlsx : ExportFormat::Csv;
        $builder = $this->builder();
        $result = $builder->build();
        $caption = $this->caption($result);

        $header = [__('research.charts.dimensions.'.$result['dimension']), ...array_map(fn (array $series): string => $series['label'], $result['series'])];
        $rows = [];
        foreach ($result['labels'] as $i => $label) {
            $rows[] = [$label, ...array_map(fn (array $series) => ChartBuilder::isSmall($series['bases'][$i]) ? static::format($series['values'][$i], $result['measure']).'*' : $series['values'][$i], $result['series'])];
        }

        $top = [['CourtWatch'], [$caption['title'].' · '.$caption['subtitle']], [$caption['filters']], []];
        $bottom = [[], ...array_map(fn (string $note): array => [$note], $caption['notes']), [$caption['source']]];

        app(ExportService::class)->log('research:chart', $format, ExportLog::CONTEXT_RESEARCHER, $user, $builder->toArray(), count($rows));

        $name = 'courtwatch-chart-'.now()->format('Y-m-d').'.'.$format->value;

        if ($format === ExportFormat::Xlsx) {
            return response()->streamDownload(function () use ($top, $header, $rows, $bottom): void {
                $writer = new Writer;
                $writer->openToFile('php://output');
                $bold = (new Style)->setFontBold();
                $brand = (new Style)->setFontBold()->setFontSize(16)->setFontColor('0C2A42');
                foreach ($top as $i => $line) {
                    $writer->addRow(Row::fromValues($line, $i === 0 ? $brand : ($i === 1 ? $bold : null)));
                }
                $writer->addRow(Row::fromValues($header, (new Style)->setFontBold()->setBackgroundColor('F8C33A')));
                foreach ([...$rows, ...$bottom] as $line) {
                    $writer->addRow(Row::fromValues($line));
                }
                $writer->close();
            }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'no-store, private']);
        }

        return response()->streamDownload(function () use ($top, $header, $rows, $bottom): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            foreach ([...$top, $header, ...$rows, ...$bottom] as $row) {
                fputcsv($out, $row, escape: '');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    /**
     * A picture of the chart or the table is drawn in the browser; this records it in the export log first.
     */
    public function logImage(string $what): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && static::canAccess(), 403);

        $builder = $this->builder();
        app(ExportService::class)->log('research:chart-'.($what === 'table' ? 'table' : 'image'), ExportFormat::Png, ExportLog::CONTEXT_RESEARCHER, $user, $builder->toArray(), count($builder->build()['labels']));
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
            'groups' => ChartBuilder::GROUPS,
            'chartTypes' => collect(self::CHART_TYPES)->mapWithKeys(fn (string $key): array => [$key => __('research.charts.types.'.$key)])->all(),
            'caption' => $this->caption($result),
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
            $measure === 'avg_accused' => number_format((float) $value, 1, ',', '.'),
            $measure === 'avg_delay' => number_format((float) $value, 1, ',', '.').' '.__('research.charts.minutes'),
            default => number_format((float) $value, 0, ',', '.'),
        };
    }
}
