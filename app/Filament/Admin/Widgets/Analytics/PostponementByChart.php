<?php

namespace App\Filament\Admin\Widgets\Analytics;

class PostponementByChart extends AnalyticsChart
{
    public ?string $filter = 'court';

    public function getHeading(): string
    {
        return __('dashboard.charts.postponement_rate');
    }

    protected function getFilters(): ?array
    {
        return [
            'court' => __('dashboard.dimensions.court'),
            'judge' => __('dashboard.dimensions.judge'),
            'case_type' => __('dashboard.dimensions.case_type'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = $this->analytics()->postponementBy($this->filter ?? 'court');

        return [
            'datasets' => [[
                'label' => __('dashboard.charts.postponement_rate_percent'),
                'data' => array_column($rows, 'rate'),
                'backgroundColor' => self::PALETTE[1],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(fn (array $row): string => $row['label'].' ('.$row['total'].')', $rows),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'max' => 100]],
        ];
    }
}
