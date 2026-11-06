<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Enums\DelayCause;

class PunctualityChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.punctuality');
    }

    public function getDescription(): ?string
    {
        $data = $this->analytics()->punctuality();

        return __('dashboard.charts.punctuality_description', [
            'percent' => self::percent($data['on_time'], $data['on_time'] + $data['late']),
            'minutes' => $data['avg_delay'] ?? '—',
        ]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->punctuality()['causes'], fn (string $key): string => DelayCause::from($key)->getLabel(), __('dashboard.charts.delay_causes'));
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true]]];
    }
}
