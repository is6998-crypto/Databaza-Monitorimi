<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Pages;

use App\Enums\CasePriority;
use App\Enums\ScheduledHearingStatus;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\Reference\Court;
use App\Models\ScheduledHearing;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;

/**
 * The month view of the hearing calendar: one cell per day with its hearings (time, case, who attends). On a phone
 * the same month is a list of days. High-priority hearings that nobody attends yet stand out.
 */
class HearingCalendar extends Page
{
    protected static string $resource = ScheduledHearingResource::class;

    protected string $view = 'filament.admin.calendar.month';

    #[Url]
    public string $month = '';

    #[Url]
    public ?string $court = null;

    #[Url]
    public bool $mine = false;

    public function mount(): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->month) !== 1) {
            $this->month = ScheduledHearing::today()->format('Y-m');
        }
    }

    public function getTitle(): string
    {
        return __('calendar.navigation');
    }

    public function previousMonth(): void
    {
        $this->month = $this->firstDay()->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->firstDay()->addMonthNoOverflow()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = ScheduledHearing::today()->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label(__('calendar.list_view'))
                ->icon(Heroicon::OutlinedListBullet)
                ->color('gray')
                ->url(ScheduledHearingResource::getUrl('list')),
            CreateAction::make()->url(ScheduledHearingResource::getUrl('create')),
        ];
    }

    private function firstDay(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')?->startOfDay() ?? ScheduledHearing::today()->startOfMonth();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $first = $this->firstDay();
        $start = $first->copy()->startOfWeek(Carbon::MONDAY);
        $end = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $today = ScheduledHearing::today()->toDateString();
        $user = auth()->user();
        $monitorId = $user instanceof User ? $user->monitor?->id : null;

        $items = ScheduledHearingResource::getEloquentQuery()
            ->with(['case.court', 'monitor.user'])
            ->whereDate('scheduled_on', '>=', $start->toDateString())
            ->whereDate('scheduled_on', '<=', $end->toDateString())
            ->when(filled($this->court), fn (Builder $query) => $query->whereHas('case', fn (Builder $case) => $case->where('court_id', (int) $this->court)))
            ->when($this->mine, fn (Builder $query) => $query->where('monitor_id', $monitorId))
            ->chronological()
            ->get()
            ->map(fn (ScheduledHearing $scheduled): array => $this->item($scheduled))
            ->groupBy('date');

        $weeks = [];
        $agenda = [];
        $index = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay(), $index++) {
            $date = $day->toDateString();
            $cell = [
                'date' => $date,
                'day' => (int) $day->format('j'),
                'label' => $this->dayLabel($day),
                'in_month' => $day->month === $first->month,
                'today' => $date === $today,
                'past' => $date < $today,
                'items' => $items->get($date, collect())->all(),
                'create_url' => $date >= $today && Gate::allows('create', ScheduledHearing::class)
                    ? ScheduledHearingResource::getUrl('create', ['date' => $date]) : null,
            ];

            $weeks[intdiv($index, 7)][] = $cell;

            if ($cell['in_month'] && $cell['items'] !== []) {
                $agenda[] = $cell;
            }
        }

        return [
            'weeks' => $weeks,
            'agenda' => $agenda,
            'monthLabel' => mb_convert_case($first->copy()->locale(app()->getLocale())->translatedFormat('F Y'), MB_CASE_TITLE),
            'weekdays' => (array) __('calendar.weekdays'),
            'courts' => Court::query()->where('is_active', true)->orderBy('name_sq')->get()->mapWithKeys(fn (Court $court): array => [$court->id => $court->label()])->all(),
            'canFilterMine' => $monitorId !== null,
            'total' => $items->flatten(1)->filter(fn (array $item): bool => str_starts_with($item['date'], $this->month))->count(),
            'unstaffed' => $items->flatten(1)->filter(fn (array $item): bool => str_starts_with($item['date'], $this->month) && $item['needs_monitor'])->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(ScheduledHearing $scheduled): array
    {
        $status = $scheduled->status();
        $case = $scheduled->case;

        return [
            'id' => $scheduled->id,
            'date' => $scheduled->scheduled_on->toDateString(),
            'time' => $scheduled->scheduled_time,
            'case' => $case->court_case_number ?: '#'.$case->id,
            'court' => $case->court?->label(),
            'courtroom' => $scheduled->courtroom,
            'monitor' => $scheduled->monitor?->user?->name,
            'status' => $status,
            'high' => $case->priority === CasePriority::High,
            'needs_monitor' => $scheduled->monitor_id === null && $status === ScheduledHearingStatus::Upcoming,
            'url' => match (true) {
                Gate::allows('update', $scheduled) => ScheduledHearingResource::getUrl('edit', ['record' => $scheduled]),
                Gate::allows('view', $case) => CourtCaseResource::getUrl('view', ['record' => $case]),
                default => null,
            },
        ];
    }

    private function dayLabel(Carbon $day): string
    {
        $weekdays = (array) __('calendar.weekdays_long');

        return ($weekdays[$day->dayOfWeekIso - 1] ?? '').', '.$day->format((string) config('courtwatch.date_format'));
    }
}
