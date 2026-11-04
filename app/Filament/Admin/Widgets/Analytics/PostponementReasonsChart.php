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
        return 'doughnut';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->postponementReasons(), fn (string $key): string => PostponementReason::from($key)->getLabel());
    }
}
