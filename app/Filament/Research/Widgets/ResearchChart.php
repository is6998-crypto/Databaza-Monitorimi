<?php

namespace App\Filament\Research\Widgets;

use App\Filament\Admin\Widgets\Analytics\AnalyticsChart;
use App\Models\User;
use App\Services\Research\ChartBuilder;
use App\Support\Brand;

/**
 * Draws the chart builder's result (App\Services\Research\ChartBuilder) with the dashboard's one chart style:
 * navy for a single series, the brand palette for compared series; bars across for categories, lines for time.
 * The page re-mounts it (a new key) whenever the result changes.
 */
class ResearchChart extends AnalyticsChart
{
    /** The brand palette reordered so neighbouring series never get the two near-identical navies. */
    private const SERIES = ['#0c2a42', '#f8c33a', '#52769d', '#fde38f', '#94a0ae', '#b3c6da', '#364254', '#fbd35c', '#e2e5ea', '#183852'];

    /** @var array<string, mixed> */
    public array $result = [];

    /** One of Charts::CHART_TYPES (resolved, so never "auto" or "table"). */
    public string $chartType = 'bar';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isApprovedResearcher();
    }

    /** Bars across grow with the number of rows (and of compared series side by side). */
    protected function getMaxHeight(): ?string
    {
        return $this->height().'px';
    }

    private function height(): int
    {
        if ($this->slices() || $this->chartType === 'radar') {
            return 460;
        }

        if (! $this->horizontal()) {
            return 420;
        }

        $rows = count($this->result['labels'] ?? []) * (in_array($this->chartType, ['bar', 'horizontal'], true) ? max(1, count($this->result['series'] ?? [])) : 1);

        return max(240, min(1400, 72 + $rows * 26));
    }

    public function getHeading(): ?string
    {
        return null;
    }

    protected function getType(): string
    {
        return match ($this->chartType) {
            'line', 'area' => 'line',
            'pie' => 'pie',
            'doughnut' => 'doughnut',
            'polar' => 'polarArea',
            'radar' => 'radar',
            default => 'bar',
        };
    }

    private function slices(): bool
    {
        return in_array($this->chartType, ['pie', 'doughnut', 'polar'], true);
    }

    private function lines(): bool
    {
        return in_array($this->chartType, ['line', 'area', 'radar'], true);
    }

    /**
     * For 100 % stacks: each value as its share of the column.
     *
     * @param  list<array<string, mixed>>  $series
     * @return list<array<string, mixed>>
     */
    private function asShares(array $series): array
    {
        $totals = [];
        foreach ($series as $row) {
            foreach ($row['values'] as $i => $value) {
                $totals[$i] = ($totals[$i] ?? 0) + (float) $value;
            }
        }

        return array_map(fn (array $row): array => [...$row, 'values' => array_map(
            fn ($value, int $i) => $totals[$i] > 0 ? round(100 * (float) $value / $totals[$i], 1) : null,
            $row['values'], array_keys($row['values']),
        )], $series);
    }

    protected function getData(): array
    {
        $series = $this->result['series'] ?? [];
        $series = $this->chartType === 'stacked_percent' ? $this->asShares($series) : $series;
        $single = count($series) === 1;
        $palette = fn (int $count): array => array_map(fn (int $i): string => self::SERIES[$i % count(self::SERIES)], range(0, max(0, $count - 1)));

        return [
            'labels' => $this->result['labels'] ?? [],
            'datasets' => array_map(function (array $row, int $i) use ($single, $palette): array {
                $colour = $single ? Brand::NAVY : self::SERIES[$i % count(self::SERIES)];

                if ($this->slices()) {
                    // Each slice its own colour, white gaps between them.
                    return ['label' => $row['label'], 'data' => $row['values'], 'backgroundColor' => $palette(count($row['values'])), 'borderColor' => '#ffffff', 'borderWidth' => 2];
                }

                return [
                    'label' => $row['label'],
                    'data' => $row['values'],
                    'backgroundColor' => $this->chartType === 'area' || $this->chartType === 'radar' ? $colour.'40' : $colour,
                    'borderColor' => $colour,
                    'borderWidth' => $this->lines() ? 2 : 0,
                    'pointRadius' => $this->lines() ? 3 : 0,
                    'fill' => $this->chartType === 'area' ? ($i === 0 ? 'origin' : '-1') : $this->chartType === 'radar',
                    'tension' => 0,
                    'spanGaps' => true,
                    // The palette has five distinct hues; from the sixth series on, lines are dashed.
                    'borderDash' => $this->lines() && $i >= 5 ? [6, 4] : [],
                ];
            }, $series, array_keys($series)),
        ];
    }

    protected function getOptions(): array
    {
        if ($this->slices() || $this->chartType === 'radar') {
            $options = $this->chartOptions([
                'aspectRatio' => 1.4,
                'plugins' => ['legend' => ['display' => true, 'position' => 'right']],
            ]);
            // Round charts have no x and y axes; polar and radar charts have one radial axis.
            $options['scales'] = in_array($this->chartType, ['polar', 'radar'], true) ? ['r' => ['beginAtZero' => true]] : new \stdClass;

            return $options;
        }

        $stacked = in_array($this->chartType, ['stacked', 'stacked_percent', 'area'], true) && count($this->result['series'] ?? []) > 1;
        $valueAxis = ['beginAtZero' => true, 'stacked' => $stacked, 'ticks' => ['precision' => 0]];

        if (($this->result['rate'] ?? false) || $this->chartType === 'stacked_percent') {
            $valueAxis['max'] = $stacked && $this->chartType !== 'stacked_percent' ? null : 100;
        }

        $categoryAxis = ['stacked' => $stacked, 'grid' => ['display' => false]];

        return $this->chartOptions([
            // Chart.js sizes the chart as width / ratio, capped by the maximum height: a low ratio lets the cap
            // decide on wide screens and still leaves a usable height on a phone.
            'aspectRatio' => $this->horizontal() ? 0.6 : 1.6,
            'indexAxis' => $this->horizontal() ? 'y' : 'x',
            'plugins' => ['legend' => ['display' => count($this->result['series'] ?? []) > 1, 'position' => 'bottom']],
            'scales' => $this->horizontal() ? ['x' => $valueAxis, 'y' => $categoryAxis] : ['x' => $categoryAxis, 'y' => $valueAxis],
        ]);
    }

    /** Categories (courts, offence categories…) read best as bars across; time runs left to right. */
    private function horizontal(): bool
    {
        return $this->chartType === 'horizontal'
            || (in_array($this->chartType, ['bar', 'stacked', 'stacked_percent'], true) && ! in_array($this->result['dimension'] ?? 'year', ChartBuilder::TIME, true));
    }
}
