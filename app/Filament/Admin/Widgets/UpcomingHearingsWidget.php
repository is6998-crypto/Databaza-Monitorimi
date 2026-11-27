<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\CasePriority;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard: the hearings of the next seven days. A monitor sees the ones they attend and the ones in their courts
 * that nobody attends yet; editors see all of them, with the high-priority hearings that have no monitor first.
 */
class UpcomingHearingsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.upcoming-hearings';

    private const SHOWN = 6;

    private const DAYS = 7;

    public static function canView(): bool
    {
        return auth()->user() instanceof User && Gate::allows('viewAny', ScheduledHearing::class);
    }

    /**
     * @return Builder<ScheduledHearing>
     */
    private function query(): Builder
    {
        $user = auth()->user();
        $monitorId = $user instanceof User ? $user->monitor?->id : null;

        return ScheduledHearingResource::getEloquentQuery()
            ->upcoming()
            ->whereDate('scheduled_on', '<=', ScheduledHearing::today()->addDays(self::DAYS)->toDateString())
            ->when($user instanceof User && $user->isMonitorOnly(), fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereNull('monitor_id')->orWhere('monitor_id', $monitorId)
            ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $monitorId = $user instanceof User ? $user->monitor?->id : null;
        $total = $this->query()->count();

        $rows = $this->query()
            ->with(['case.court', 'monitor.user'])
            ->join('cases', 'cases.id', '=', 'scheduled_hearings.case_id')
            ->select('scheduled_hearings.*')
            // Unattended high-priority hearings first, then by day and time.
            ->orderByRaw("CASE WHEN scheduled_hearings.monitor_id IS NULL AND cases.priority = 'high' THEN 0 ELSE 1 END")
            ->chronological()
            ->limit(self::SHOWN)
            ->get()
            ->map(fn (ScheduledHearing $scheduled): array => [
                'day' => $scheduled->scheduled_on->format('d'),
                'month' => mb_strtoupper(mb_substr((string) $scheduled->scheduled_on->copy()->locale(app()->getLocale())->translatedFormat('M'), 0, 3)),
                'time' => $scheduled->scheduled_time,
                'case' => $scheduled->case->court_case_number ?: '#'.$scheduled->case->id,
                'court' => $scheduled->case->court?->label(),
                'monitor' => $scheduled->monitor?->user?->name,
                'mine' => $monitorId !== null && $scheduled->monitor_id === $monitorId,
                'high' => $scheduled->case->priority === CasePriority::High,
                'url' => Gate::allows('update', $scheduled)
                    ? ScheduledHearingResource::getUrl('edit', ['record' => $scheduled])
                    : CourtCaseResource::getUrl('view', ['record' => $scheduled->case_id]),
            ])
            ->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'more' => max(0, $total - count($rows)),
            'allUrl' => ScheduledHearingResource::getUrl(),
        ];
    }
}
