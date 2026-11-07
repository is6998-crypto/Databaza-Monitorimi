<?php

namespace App\Filament\Admin\Widgets\Analytics;

class StageDurationsChart extends AnalyticsChart
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.charts.stage_durations');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = $this->analytics()->stageDurations();

        return [
            'datasets' => [[
                'label' => __('dashboard.charts.median_days'),
                'data' => array_map(fn (array $row): ?float => $row['median'], $rows),
                'backgroundColor' => self::PALETTE[0],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(fn (array $row): string => $row['label'].' (n='.$row['cases'].')', $rows),
        ];
    }

    protected function getOptions(): array
    {
        return $this->chartOptions(['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['beginAtZero' => true], 'y' => ['grid' => ['display' => false]]]]);
    }
}
