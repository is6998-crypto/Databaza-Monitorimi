<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Pages;

use App\Enums\ScheduledHearingStatus;
use App\Filament\Admin\Resources\ScheduledHearings\ScheduledHearingResource;
use App\Models\ScheduledHearing;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListScheduledHearings extends ListRecords
{
    protected static string $resource = ScheduledHearingResource::class;

    public function getTitle(): string
    {
        return __('calendar.list_title');
    }

    public function getSubheading(): ?string
    {
        return __('calendar.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label(__('calendar.month_view'))
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(ScheduledHearingResource::getUrl()),
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $missed = ScheduledHearingResource::getEloquentQuery()->missed()->count();

        $tab = fn (ScheduledHearingStatus $status): Tab => Tab::make(__("calendar.tabs.{$status->value}"))
            ->modifyQueryUsing(function (Builder $query) use ($status): Builder {
                ScheduledHearing::constrain($query, $status->value);

                return $query;
            });

        return [
            'upcoming' => $tab(ScheduledHearingStatus::Upcoming),
            'missed' => $tab(ScheduledHearingStatus::Missed)->badge($missed > 0 ? (string) $missed : null)->badgeColor('warning'),
            'attended' => $tab(ScheduledHearingStatus::Attended),
            'cancelled' => $tab(ScheduledHearingStatus::Cancelled),
            'all' => Tab::make(__('calendar.tabs.all')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'upcoming';
    }
}
