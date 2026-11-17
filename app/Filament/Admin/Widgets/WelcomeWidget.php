<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Pages\ReviewQueue;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * The band at the top of the staff dashboard: whose workspace this is (name, role, today's date), the next
 * things this person can do, and a row of live counts of what is waiting for them (the review queue and reports
 * for an editor, drafts and returned reports for a monitor, legal time limits for both). Every action and count
 * is shown only when the panel's own access checks allow it (the policies still decide on the target page).
 */
class WelcomeWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.welcome';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $today = Carbon::now((string) config('courtwatch.display_timezone', 'Europe/Belgrade'))->locale(app()->getLocale());

        $actions = [];

        if (CourtCaseResource::canCreate()) {
            $actions[] = ['label' => __('dashboard.welcome.new_case'), 'url' => CourtCaseResource::getUrl('create'), 'icon' => 'heroicon-o-folder-plus'];
        }

        if (HearingResource::canCreate()) {
            $actions[] = ['label' => __('dashboard.welcome.new_hearing'), 'url' => HearingResource::getUrl('create'), 'icon' => 'heroicon-o-calendar-days'];
        }

        if ($user->hasRole(Role::Monitor->value)) {
            $actions[] = ['label' => __('dashboard.welcome.monitor_app'), 'url' => route('monitor.app'), 'icon' => 'heroicon-o-device-phone-mobile'];
        }

        $role = collect(Role::staff())->first(fn (Role $role): bool => $user->hasRole($role->value));

        return [
            'firstName' => Str::of((string) $user->name)->trim()->before(' ')->title()->toString(),
            'role' => $role?->getLabel(),
            'today' => Str::ucfirst($today->translatedFormat('l')).', '.$today->format('d.m.Y'),
            'actions' => $actions,
            'pulse' => $this->pulse($user),
        ];
    }

    /**
     * What is waiting for this person, as numbers that link to the list behind them.
     *
     * @return list<array{label: string, value: int, url: string, icon: string, alert: bool}>
     */
    private function pulse(User $user): array
    {
        $pulse = [];

        if (ReviewQueue::canAccess()) {
            $pulse[] = [
                'label' => __('dashboard.pulse.review'),
                'value' => CourtCase::query()->awaitingReview()->count() + Hearing::query()->awaitingReview()->count(),
                'url' => ReviewQueue::getUrl(),
                'icon' => 'heroicon-o-inbox-stack',
                'alert' => false,
            ];

            $pulse[] = [
                'label' => __('dashboard.pulse.reports'),
                'value' => HearingReport::query()->awaitingReview()->count(),
                'url' => HearingReportResource::getUrl('index', ['filters' => ['status' => ['values' => [ReportStatus::Submitted->value]]]]),
                'icon' => 'heroicon-o-document-text',
                'alert' => false,
            ];
        } elseif ($user->hasRole(Role::Monitor->value)) {
            $pulse[] = [
                'label' => __('dashboard.pulse.my_drafts'),
                'value' => Hearing::query()->where('created_by', $user->id)->whereIn('status', [WorkflowStatus::Draft->value, WorkflowStatus::Returned->value])->count(),
                'url' => HearingResource::getUrl('index', ['filters' => ['status' => ['values' => [WorkflowStatus::Draft->value, WorkflowStatus::Returned->value]]]]),
                'icon' => 'heroicon-o-pencil-square',
                'alert' => false,
            ];

            $pulse[] = [
                'label' => __('dashboard.pulse.reports_returned'),
                'value' => HearingReport::query()->visibleTo($user)->where('status', ReportStatus::Returned->value)->count(),
                'url' => HearingReportResource::getUrl('index', ['filters' => ['status' => ['values' => [ReportStatus::Returned->value]]]]),
                'icon' => 'heroicon-o-document-text',
                'alert' => false,
            ];
        }

        if (Gate::forUser($user)->allows('viewAny', CaseDeadline::class)) {
            $overdue = CaseDeadlineResource::getEloquentQuery()->overdue()->count();

            $pulse[] = [
                'label' => __('dashboard.pulse.overdue'),
                'value' => $overdue,
                'url' => CaseDeadlineResource::getUrl('index', ['tab' => 'overdue']),
                'icon' => 'heroicon-o-clock',
                'alert' => $overdue > 0,
            ];

            $pulse[] = [
                'label' => __('dashboard.pulse.due_soon'),
                'value' => CaseDeadlineResource::getEloquentQuery()->dueSoon()->count(),
                'url' => CaseDeadlineResource::getUrl('index', ['tab' => 'due_soon']),
                'icon' => 'heroicon-o-bell-alert',
                'alert' => false,
            ];
        }

        return $pulse;
    }
}
