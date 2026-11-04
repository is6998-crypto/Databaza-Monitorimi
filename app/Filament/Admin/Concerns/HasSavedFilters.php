<?php

namespace App\Filament\Admin\Concerns;

use App\Models\SavedFilter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

/**
 * Saved filters for list pages: the current filters and search (which also live in the URL)
 * can be saved under a name, optionally shared with colleagues, and re-applied in one click.
 */
trait HasSavedFilters
{
    abstract public static function savedFilterKey(): string;

    /**
     * @return list<Action|ActionGroup>
     */
    protected function savedFilterActions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $saved = SavedFilter::query()->availableTo($user, static::savedFilterKey())->get();

        $apply = $saved->map(fn (SavedFilter $filter): Action => Action::make('applySavedFilter'.$filter->id)
            ->label($filter->name.($filter->is_shared && $filter->user_id !== $user->id ? ' · '.__('filters.shared') : ''))
            ->icon($filter->is_shared ? Heroicon::OutlinedUserGroup : Heroicon::OutlinedBookmark)
            ->url(static::getResource()::getUrl('index', array_filter([
                'filters' => $filter->filters['filters'] ?? null,
                'search' => $filter->filters['search'] ?? null,
            ]))))->all();

        return [
            ActionGroup::make([
                ...$apply,
                Action::make('deleteSavedFilter')
                    ->label(__('filters.delete'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (): bool => $saved->contains('user_id', $user->id))
                    ->schema([
                        Select::make('saved_filter_id')
                            ->label(__('filters.name'))
                            ->options($saved->where('user_id', $user->id)->pluck('name', 'id')->all())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $filter = SavedFilter::query()->findOrFail($data['saved_filter_id']);
                        Gate::authorize('delete', $filter);
                        $filter->delete();
                        Notification::make()->title(__('filters.deleted'))->success()->send();
                    }),
            ])
                ->label(__('filters.saved'))
                ->icon(Heroicon::OutlinedBookmarkSquare)
                ->button()
                ->color('gray')
                ->visible($saved->isNotEmpty()),

            Action::make('saveFilters')
                ->label(__('filters.save'))
                ->icon(Heroicon::OutlinedBookmark)
                ->color('gray')
                ->schema([
                    TextInput::make('name')->label(__('filters.name'))->required()->maxLength(100),
                    Toggle::make('is_shared')->label(__('filters.share'))->helperText(__('filters.share_help')),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    abort_unless($user instanceof User && Gate::forUser($user)->allows('create', SavedFilter::class), 403);

                    SavedFilter::query()->create([
                        'user_id' => $user->id,
                        'resource' => static::savedFilterKey(),
                        'name' => $data['name'],
                        'is_shared' => (bool) ($data['is_shared'] ?? false),
                        'filters' => [
                            'filters' => $this->tableFilters,
                            'search' => filled($this->tableSearch) ? $this->tableSearch : null,
                        ],
                    ]);

                    Notification::make()->title(__('filters.saved_done'))->success()->send();
                }),
        ];
    }
}
