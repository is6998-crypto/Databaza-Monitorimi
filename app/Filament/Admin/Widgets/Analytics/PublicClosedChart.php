<?php

namespace App\Filament\Admin\Widgets\Analytics;

class PublicClosedChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.public_closed');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->publicVsClosed(), fn (string $key): string => __('hearings.answers.'.$key), ranked: true);
    }

    /** Ranked horizontal bars in one hue (it was a doughnut: bars on one baseline compare more exactly). */
    protected function getOptions(): array
    {
        return $this->chartOptions([
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true], 'y' => ['grid' => ['display' => false]]],
        ]);
    }
}
