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
        return 'doughnut';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->publicVsClosed(), fn (string $key): string => __('hearings.answers.'.$key));
    }
}
