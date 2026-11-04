<?php

namespace App\Filament\Admin\Widgets\Analytics;

class ComplianceChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.compliance');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = $this->analytics()->compliance();

        return $this->single([
            'recorded' => self::percent($data['recorded'], $data['recording_total']),
            'interpretation' => self::percent($data['interpretation_provided'], $data['interpretation_needed']),
        ], fn (string $key): string => __('dashboard.charts.compliance_'.$key), __('dashboard.charts.percent'));
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'max' => 100]]];
    }
}
