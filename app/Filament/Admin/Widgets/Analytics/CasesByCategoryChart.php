<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Models\Reference\Category;

/**
 * Cases per statistics category of the offences charged (the categories of the criminal-offence list).
 * Full width and taller than the others: up to 28 ranked bars.
 */
class CasesByCategoryChart extends AnalyticsChart
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '560px';

    public function getHeading(): string
    {
        return __('dashboard.charts.cases_by_category');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = $this->analytics()->casesByCategory();
        $labels = Category::query()->whereIn('id', array_keys($counts))->get()
            ->mapWithKeys(fn (Category $category): array => [(string) $category->id => $category->label()]);

        return $this->single($counts, fn (string $id): string => (string) ($labels[$id] ?? '—'), __('dashboard.charts.cases'), ranked: true);
    }

    protected function getOptions(): array
    {
        return $this->chartOptions([
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'y' => ['grid' => ['display' => false]]],
        ]);
    }
}
