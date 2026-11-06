<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\MeasureKind;

class SeizureConfiscationChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.seizure');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = $this->analytics()->seizureConfiscation();
        $labels = array_map(fn (string $kind): string => MeasureKind::from($kind)->getLabel(), array_keys($data));

        return [
            'datasets' => [
                ['label' => __('dashboard.charts.requested'), 'data' => array_column($data, 'requested'), 'backgroundColor' => self::PALETTE[7], 'borderWidth' => 0],
                ['label' => __('dashboard.charts.granted'), 'data' => array_column($data, 'granted'), 'backgroundColor' => self::PALETTE[2], 'borderWidth' => 0],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true]]];
    }
}
