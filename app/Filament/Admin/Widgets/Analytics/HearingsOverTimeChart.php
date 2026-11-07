<?php

namespace App\Filament\Admin\Widgets\Analytics;

use Illuminate\Support\Carbon;

class HearingsOverTimeChart extends AnalyticsChart
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return __('dashboard.charts.hearings_over_time');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->analytics()->hearingsOverTime();

        return [
            'datasets' => [[
                'label' => __('dashboard.stats.hearings'),
                'data' => array_values($series),
                'borderColor' => self::PALETTE[0],
                'backgroundColor' => 'rgba(248, 195, 58, 0.22)',
                'pointBackgroundColor' => self::PALETTE[0],
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => array_map(fn (string $month): string => Carbon::createFromFormat('Y-m', $month)->format('m.Y'), array_keys($series)),
        ];
    }

    /** One dataset (named in the heading), month labels kept straight and thinned out. */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'elements' => ['point' => ['radius' => 2, 'hoverRadius' => 5]],
            'scales' => [
                'x' => ['grid' => ['display' => false], 'ticks' => ['autoSkip' => true, 'maxRotation' => 0, 'maxTicksLimit' => 12]],
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}
