<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Models\CaseDeadline;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard: the legal time limits that are exceeded right now, the longest overdue first, each with a meter
 * that sets the days over beside the limit itself. Shown to the people who are notified about them (monitors see
 * their own courts and cases); hidden when there is nothing to show.
 */
class OverdueDeadlinesWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.overdue-deadlines';

    /** How many are listed; the rest are one click away. */
    private const SHOWN = 5;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && Gate::allows('viewAny', CaseDeadline::class)
            && CaseDeadlineResource::getEloquentQuery()->overdue()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $query = CaseDeadlineResource::getEloquentQuery()->overdue();
        $total = (clone $query)->count();

        $rows = $query->with('case.court')->orderBy('due_on')->limit(self::SHOWN)->get()
            ->map(fn (CaseDeadline $deadline): array => [
                'phase' => $deadline->rule->getLabel(),
                'article' => __('deadlines.article', ['article' => $deadline->rule->article()]),
                'case' => $deadline->case->court_case_number ?: '#'.$deadline->case->id,
                'court' => $deadline->case->court?->label(),
                'limit' => $deadline->limitLabel(),
                'over' => trans_choice('deadlines.units.days', $deadline->daysOver(), ['count' => $deadline->daysOver()]),
                // Share of the bar that is the limit itself; the rest is the time over it.
                'limitShare' => $deadline->limitShare(),
                'url' => CourtCaseResource::getUrl('view', ['record' => $deadline->case_id]),
            ])
            ->all();

        return [
            'rows' => $rows,
            'total' => $total,
            'more' => max(0, $total - count($rows)),
            'allUrl' => CaseDeadlineResource::getUrl('index', ['tab' => 'overdue']),
        ];
    }
}
