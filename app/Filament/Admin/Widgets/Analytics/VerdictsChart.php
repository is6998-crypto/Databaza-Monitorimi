<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\StageOutcome;

class VerdictsChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.verdicts');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->verdicts(), fn (string $key): string => StageOutcome::from($key)->getLabel());
    }
}
