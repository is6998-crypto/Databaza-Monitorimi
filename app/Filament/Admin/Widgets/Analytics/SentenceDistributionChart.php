<?php

namespace App\Filament\Admin\Widgets\Analytics;

class SentenceDistributionChart extends AnalyticsChart
{
    public function getHeading(): string
    {
        return __('dashboard.charts.sentences');
    }

    public function getDescription(): ?string
    {
        $averages = $this->analytics()->sentenceAverages();

        return __('dashboard.charts.sentences_description', [
            'requested' => $averages['requested'] ?? '—',
            'imposed' => $averages['imposed'] ?? '—',
            'suspended' => $averages['suspended'],
        ]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return $this->single($this->analytics()->sentenceDistribution(), fn (string $key): string => __('dashboard.buckets.'.$key), __('dashboard.charts.sentenced_persons'));
    }

    protected function getOptions(): array
    {
        return $this->chartOptions(['plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['beginAtZero' => true]]]);
    }
}
