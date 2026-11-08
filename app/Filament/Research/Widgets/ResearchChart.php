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

    /** bar, stacked or line */
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
        if (! $this->horizontal()) {
            return 420;
        }

        $rows = count($this->result['labels'] ?? []) * ($this->chartType === 'bar' ? max(1, count($this->result['series'] ?? [])) : 1);

        return max(240, min(1400, 72 + $rows * 26));
    }

    public function getHeading(): ?string
    {
        return null;
    }

    protected function getType(): string
    {
        return $this->chartType === 'line' ? 'line' : 'bar';
    }

    protected function getData(): array
    {
        $series = $this->result['series'] ?? [];
        $single = count($series) === 1;

        return [
            'labels' => $this->result['labels'] ?? [],
            'datasets' => array_map(function (array $row, int $i) use ($single): array {
                $colour = $single ? Brand::NAVY : self::SERIES[$i % count(self::SERIES)];

                return [
                    'label' => $row['label'],
                    'data' => $row['values'],
                    'backgroundColor' => $colour,
                    'borderColor' => $colour,
                    'borderWidth' => $this->chartType === 'line' ? 2 : 0,
                    'pointRadius' => $this->chartType === 'line' ? 3 : 0,
                    'tension' => 0,
                    'spanGaps' => true,
                    // The palette has five distinct hues; from the sixth series on, lines are dashed.
                    'borderDash' => $this->chartType === 'line' && $i >= 5 ? [6, 4] : [],
                ];
            }, $series, array_keys($series)),
        ];
    }

    protected function getOptions(): array
    {
        $stacked = $this->chartType === 'stacked';
        $valueAxis = ['beginAtZero' => true, 'stacked' => $stacked, 'ticks' => ['precision' => 0]];

        if ($this->result['rate'] ?? false) {
            $valueAxis['max'] = $stacked ? null : 100;
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
        return $this->chartType !== 'line' && ! in_array($this->result['dimension'] ?? 'year', ChartBuilder::TIME, true);
    }
}
