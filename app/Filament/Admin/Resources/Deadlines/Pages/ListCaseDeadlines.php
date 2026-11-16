<?php

namespace App\Filament\Admin\Resources\Deadlines\Pages;

use App\Enums\DeadlineState;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Models\CaseDeadline;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCaseDeadlines extends ListRecords
{
    protected static string $resource = CaseDeadlineResource::class;

    public function getSubheading(): ?string
    {
        return __('deadlines.subheading');
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $overdue = CaseDeadlineResource::getEloquentQuery()->overdue()->count();
        $dueSoon = CaseDeadlineResource::getEloquentQuery()->dueSoon()->count();

        $tab = fn (string $status): Tab => Tab::make(__("deadlines.tabs.{$status}"))
            ->modifyQueryUsing(function (Builder $query) use ($status): Builder {
                CaseDeadline::constrain($query, $status);

                return $query;
            });

        return [
            'overdue' => $tab(CaseDeadline::STATUS_OVERDUE)->badge($overdue > 0 ? (string) $overdue : null)->badgeColor('warning'),
            'due_soon' => $tab(CaseDeadline::STATUS_DUE_SOON)->badge($dueSoon > 0 ? (string) $dueSoon : null)->badgeColor('info'),
            'running' => $tab(CaseDeadline::STATUS_RUNNING),
            'late' => $tab(DeadlineState::Late->value),
            'all' => Tab::make(__('deadlines.tabs.all')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'overdue';
    }
}
