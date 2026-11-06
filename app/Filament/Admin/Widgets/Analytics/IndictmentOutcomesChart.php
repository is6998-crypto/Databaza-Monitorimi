<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\StageOutcome;

class IndictmentOutcomesChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.indictment_outcomes');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->indictmentOutcomes(), fn (string $key): string => StageOutcome::from($key)->getLabel());
    }
}
