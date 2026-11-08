<?php

namespace App\Filament\Admin\Widgets\Analytics;

use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Support\Permissions;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->checkPermissionTo(Permissions::VIEW_ANALYTICS) ?? false;
    }

    protected function getStats(): array
    {
        $data = AnalyticsService::for(AnalyticsFilters::fromArray($this->pageFilters ?? []))->overview();
        $rate = fn (int $part, int $total): string => $total > 0 ? number_format($part / $total * 100, 1, ',', '.').' %' : '—';

        return [
            Stat::make(__('dashboard.stats.cases'), number_format($data['cases'], 0, ',', '.'))
                ->description(__('dashboard.stats.verified_only'))
                ->icon(Heroicon::OutlinedFolderOpen),
            Stat::make(__('dashboard.stats.hearings'), number_format($data['hearings'], 0, ',', '.'))
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make(__('dashboard.stats.postponement_rate'), $rate($data['postponed'], $data['hearings']))
                ->description(__('dashboard.stats.postponed_of', ['count' => $data['postponed']]))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning'),
            Stat::make(__('dashboard.stats.late_rate'), $rate($data['late'], $data['hearings']))
                ->description($data['avg_delay'] ? __('dashboard.stats.avg_delay', ['minutes' => $data['avg_delay']]) : null)
                ->icon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make(__('dashboard.stats.public_rate'), $rate($data['public'], $data['hearings']))
                ->icon(Heroicon::OutlinedEye),
            Stat::make(__('dashboard.stats.conviction_rate'), $rate($data['convictions'], $data['judgments']))
                ->description(__('dashboard.stats.judgments', ['count' => $data['judgments']]))
                ->icon(Heroicon::OutlinedScale),
        ];
    }
}
