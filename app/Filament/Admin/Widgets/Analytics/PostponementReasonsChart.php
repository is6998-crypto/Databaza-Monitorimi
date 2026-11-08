<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\PostponementReason;

class PostponementReasonsChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.postponement_reasons');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->postponementReasons(), fn (string $key): string => PostponementReason::from($key)->getLabel(), ranked: true);
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
