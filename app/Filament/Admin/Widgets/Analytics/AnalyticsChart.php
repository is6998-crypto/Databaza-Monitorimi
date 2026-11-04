<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Support\Permissions;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Base for dashboard charts: uses the dashboard filters (date range, court, case type)
 * and is visible to users with the View:Analytics permission.
 */
abstract class AnalyticsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $maxHeight = '280px';

    public const PALETTE = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#14b8a6', '#64748b', '#ec4899', '#84cc16'];

    public static function canView(): bool
    {
        return auth()->user()?->checkPermissionTo(Permissions::VIEW_ANALYTICS) ?? false;
    }

    protected function analytics(): AnalyticsService
    {
        return AnalyticsService::for(AnalyticsFilters::fromArray($this->pageFilters ?? []));
    }

    /**
     * @param  array<string, int|float>  $values
     * @param  callable(string): string  $label
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function single(array $values, callable $label, string $datasetLabel = '', bool $colorful = true): array
    {
        $labels = array_map(fn (string $key): string => $label($key), array_keys($values));

        return [
            'datasets' => [[
                'label' => $datasetLabel,
                'data' => array_values($values),
                'backgroundColor' => $colorful ? array_slice(array_merge(self::PALETTE, self::PALETTE), 0, count($values)) : self::PALETTE[0],
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected static function percent(int|float $part, int|float $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
