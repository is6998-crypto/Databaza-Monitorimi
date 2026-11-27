<?php

namespace App\Filament\Admin\Pages;

use App\Enums\CasePriority;
use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\ScheduledHearing;
use App\Models\User;
use App\Services\Coverage\CoverageChecks;
use App\Support\Permissions;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * "Coverage": where the monitoring has gaps — active cases nobody watched lately, high-priority cases without a
 * monitor, planned hearings that were missed, hearings without a report, criminal cases whose time limits are
 * guessed, and drafts left unsubmitted. For editors and analysts.
 */
class Coverage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedViewfinderCircle;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'coverage';

    protected string $view = 'filament.admin.pages.coverage';

    /** Rows listed per check until "show all" is pressed. */
    private const SHOWN = 8;

    /** At most this many rows when everything is shown. */
    private const MAX = 200;

    /** @var list<string> */
    public array $expanded = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->checkPermissionTo(Permissions::REVIEW) || $user->checkPermissionTo(Permissions::VIEW_ANALYTICS));
    }

    public static function getNavigationLabel(): string
    {
        return __('coverage.navigation');
    }

    public function getTitle(): string
    {
        return __('coverage.title');
    }

    public function getSubheading(): ?string
    {
        return __('coverage.subheading');
    }

    public function toggle(string $check): void
    {
        if (! in_array($check, CoverageChecks::ALL, true)) {
            return;
        }

        $this->expanded = in_array($check, $this->expanded, true)
            ? array_values(array_diff($this->expanded, [$check]))
            : [...$this->expanded, $check];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $checks = app(CoverageChecks::class);
        $sections = [];

        foreach (CoverageChecks::ALL as $key) {
            $limit = in_array($key, $this->expanded, true) ? self::MAX : self::SHOWN;
            [$total, $rows] = $this->rows($checks, $key, $limit);

            $sections[$key] = [
                'key' => $key,
                'title' => __("coverage.checks.{$key}.title"),
                'description' => __("coverage.checks.{$key}.description", $this->thresholds()),
                'total' => $total,
                'rows' => $rows,
                'more' => max(0, min($total, self::MAX) - count($rows)),
                'expanded' => in_array($key, $this->expanded, true) && $total > self::SHOWN,
                'link' => $this->link($key),
            ];
        }

        return ['sections' => $sections];
    }

    /**
     * @return array<string, int>
     */
    private function thresholds(): array
    {
        return [
            'days' => CoverageChecks::days('not_followed_days', 60),
            'report_days' => CoverageChecks::days('report_after_days', 3),
            'report_window' => CoverageChecks::days('report_window_days', 90),
            'missed_days' => CoverageChecks::days('missed_hearings_days', 30),
            'draft_days' => CoverageChecks::days('stale_draft_days', 14),
        ];
    }

    /**
     * @return array{0: int, 1: list<array{title: string, sub: string|null, detail: string|null, flag: bool, url: string|null}>}
     */
    private function rows(CoverageChecks $checks, string $key, int $limit): array
    {
        $date = (string) config('courtwatch.date_format');

        $caseRow = fn (CourtCase $case, ?string $detail): array => [
            'title' => $case->court_case_number ?: '#'.$case->id,
            'sub' => $case->court?->label(),
            'detail' => $detail,
            'flag' => $case->priority === CasePriority::High,
            'url' => CourtCaseResource::getUrl('view', ['record' => $case]),
        ];

        switch ($key) {
            case CoverageChecks::NOT_FOLLOWED:
                $query = $checks->notFollowed();

                return [
                    (clone $query)->count(),
                    $query->with(['court', 'assignedMonitor.user'])
                        ->orderByRaw(CasePriority::orderSql('cases.priority'))
                        ->orderByRaw('last_hearing_on asc nulls first')
                        ->limit($limit)->get()
                        ->map(fn (CourtCase $case): array => $caseRow($case, collect([
                            $case->getAttribute('last_hearing_on')
                                ? __('coverage.last_hearing', ['date' => Carbon::parse((string) $case->getAttribute('last_hearing_on'))->format($date)])
                                : __('coverage.never_monitored'),
                            $case->assignedMonitor?->user->name ?? __('coverage.no_monitor'),
                        ])->implode(' · ')))->all(),
                ];

            case CoverageChecks::NO_MONITOR:
                $query = $checks->noMonitor();

                return [
                    (clone $query)->count(),
                    $query->with('court')->latest('updated_at')->limit($limit)->get()
                        ->map(fn (CourtCase $case): array => $caseRow($case, $case->priority_note))->all(),
                ];

            case CoverageChecks::MISSED_HEARINGS:
                $query = $checks->missedHearings();

                return [
                    (clone $query)->count(),
                    $query->with(['case.court', 'monitor.user'])->orderByDesc('scheduled_on')->limit($limit)->get()
                        ->map(fn (ScheduledHearing $scheduled): array => [
                            'title' => $scheduled->case->court_case_number ?: '#'.$scheduled->case_id,
                            'sub' => $scheduled->case->court?->label(),
                            'detail' => $scheduled->whenLabel().' · '.($scheduled->monitor?->user->name ?? __('calendar.nobody')),
                            'flag' => $scheduled->case->priority === CasePriority::High,
                            'url' => ScheduledHearingResource::getUrl('edit', ['record' => $scheduled]),
                        ])->all(),
                ];

            case CoverageChecks::MISSING_REPORTS:
                $query = $checks->missingReports();

                return [
                    (clone $query)->count(),
                    $query->with(['case.court', 'monitor.user', 'creator'])->orderBy('hearing_date')->limit($limit)->get()
                        ->map(fn (Hearing $hearing): array => [
                            'title' => $hearing->case->court_case_number ?: '#'.$hearing->case_id,
                            'sub' => $hearing->case->court?->label(),
                            'detail' => $hearing->hearing_date->format($date).' · '.($hearing->monitor?->user->name ?? $hearing->creator->name ?? '—'),
                            'flag' => $hearing->case->priority === CasePriority::High,
                            'url' => HearingResource::getUrl('view', ['record' => $hearing]),
                        ])->all(),
                ];

            case CoverageChecks::MISSING_FACTS:
                $query = $checks->missingFacts();

                return [
                    (clone $query)->count(),
                    $query->with('court')->orderByRaw(CasePriority::orderSql('cases.priority'))->latest('updated_at')->limit($limit)->get()
                        ->map(fn (CourtCase $case): array => $caseRow($case, collect([
                            $case->in_detention === null ? __('cases.fields.in_detention') : null,
                            $case->trial_bench === null ? __('cases.fields.trial_bench') : null,
                        ])->filter()->implode(' · ')))->all(),
                ];

            default:
                ['cases' => $cases, 'hearings' => $hearings] = $checks->staleDrafts();
                $total = (clone $cases)->count() + (clone $hearings)->count();

                $rows = $cases->with(['court', 'creator'])->oldest('updated_at')->limit($limit)->get()
                    ->map(fn (CourtCase $case): array => [
                        ...$caseRow($case, null),
                        'detail' => $this->draftDetail(__('cases.model'), $case->status->getLabel(), $case->creator?->name, $case->updated_at),
                    ])
                    ->concat($hearings->with(['case.court', 'creator'])->oldest('updated_at')->limit($limit)->get()
                        ->map(fn (Hearing $hearing): array => [
                            'title' => $hearing->case->court_case_number ?: '#'.$hearing->case_id,
                            'sub' => $hearing->case->court?->label(),
                            'detail' => $this->draftDetail(__('hearings.model'), $hearing->status->getLabel(), $hearing->creator?->name, $hearing->updated_at),
                            'flag' => false,
                            'url' => HearingResource::getUrl('view', ['record' => $hearing]),
                        ]))
                    ->take($limit)
                    ->values()
                    ->all();

                return [$total, $rows];
        }
    }

    private function draftDetail(string $kind, string $status, ?string $author, ?CarbonInterface $updated): string
    {
        return collect([
            ucfirst($kind).': '.$status,
            $author,
            $updated !== null ? __('coverage.untouched_since', ['date' => $updated->timezone((string) config('courtwatch.display_timezone'))->format((string) config('courtwatch.date_format'))]) : null,
        ])->filter()->implode(' · ');
    }

    private function link(string $key): ?string
    {
        return match ($key) {
            CoverageChecks::MISSED_HEARINGS => ScheduledHearingResource::getUrl('list', ['tab' => 'missed']),
            CoverageChecks::NO_MONITOR => CourtCaseResource::getUrl('index', ['tableFilters' => ['priority' => ['values' => [CasePriority::High->value]]]]),
            default => null,
        };
    }
}
