<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Support\Brand;
use App\Support\Permissions;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Base for dashboard charts: uses the dashboard filters (date range, court, case type)
 * and is visible to users with the View:Analytics permission.
 */
abstract class AnalyticsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $maxHeight = '280px';

    public const PALETTE = Brand::CHART_PALETTE;

    public static function canView(): bool
    {
        return auth()->user()?->checkPermissionTo(Permissions::VIEW_ANALYTICS) ?? false;
    }

    protected function analytics(): AnalyticsService
    {
        return AnalyticsService::for(AnalyticsFilters::fromArray($this->pageFilters ?? []));
    }

    /**
     * @param  array<string, int|float>  $values
     * @param  callable(string): string  $label
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    /**
     * One series: a single hue (navy), as every comparison on the dashboard. $ranked sorts it largest first
     * (categories to compare); leave it false where the order means something (buckets, stages).
     *
     * @param  array<string, int|float|null>  $values
     */
    protected function single(array $values, callable $label, string $datasetLabel = '', bool $ranked = false): array
    {
        if ($ranked) {
            uasort($values, fn ($a, $b): int => ($b ?? -1) <=> ($a ?? -1));
        }

        $labels = array_map(fn (string $key): string => $label($key), array_keys($values));

        return [
            'datasets' => [[
                'label' => $datasetLabel,
                'data' => array_values($values),
                'backgroundColor' => Brand::NAVY,
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * The dashboard's one chart style (the same rules as the public portal): bars at most 24px thick with a 4px
     * rounded data end and a square baseline, hairline grids without axis borders, 13px grey ticks, a white
     * tooltip, and a legend only where there are two series. A widget passes its own options to merge in.
     *
     * @param  array<string, mixed>  $own
     * @return array<string, mixed>
     */
    protected function chartOptions(array $own = []): array
    {
        $axis = [
            'border' => ['display' => false],
            'grid' => ['color' => '#eceef1', 'drawTicks' => false],
            'ticks' => ['color' => '#677386', 'font' => ['size' => 13], 'padding' => 8],
        ];

        return array_replace_recursive([
            'datasets' => [
                'bar' => ['maxBarThickness' => 24, 'borderRadius' => 4, 'borderSkipped' => 'start', 'categoryPercentage' => 0.7],
            ],
            'elements' => [
                'line' => ['borderWidth' => 2, 'tension' => 0.3],
                'point' => ['radius' => 0, 'hoverRadius' => 5, 'hitRadius' => 12],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'pointStyle' => 'circle', 'boxWidth' => 8, 'boxHeight' => 8, 'color' => '#364254', 'font' => ['size' => 13], 'padding' => 16]],
                'tooltip' => [
                    'backgroundColor' => '#ffffff', 'titleColor' => '#0c2a42', 'bodyColor' => '#364254',
                    'borderColor' => '#e2e5ea', 'borderWidth' => 1, 'cornerRadius' => 10, 'padding' => 10,
                    'boxPadding' => 4, 'usePointStyle' => true,
                ],
            ],
            'scales' => [
                'x' => $axis,
                'y' => $axis,
            ],
        ], $own);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return $this->chartOptions();
    }

    protected static function percent(int|float $part, int|float $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
