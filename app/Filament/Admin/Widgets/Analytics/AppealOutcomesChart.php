<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\StageOutcome;

class AppealOutcomesChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.appeals');
    }

    public function getDescription(): ?string
    {
        $plea = $this->analytics()->pleaAgreements();

        return __('dashboard.charts.plea_description', ['accepted' => $plea['accepted'], 'judgments' => $plea['judgments']]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->appealOutcomes(), fn (string $key): string => StageOutcome::from($key)->getLabel(), ranked: true);
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
