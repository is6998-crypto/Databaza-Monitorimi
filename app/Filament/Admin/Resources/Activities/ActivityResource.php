<?php

namespace App\Filament\Admin\Resources\Activities;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Activities\Pages\ListActivities;
use App\Filament\Admin\Resources\Activities\Pages\ViewActivity;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit-log viewer: every create/update/delete, login, export and permission change.
 */
class ActivityResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'audit-log';

    public static function getModelLabel(): string
    {
        return __('audit.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit.navigation');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('audit.fields.created_at'))->dateTime()->sortable(),
                TextColumn::make('log_name')->label(__('audit.fields.log_name'))->badge()->formatStateUsing(fn (?string $state): string => static::logLabel($state)),
                TextColumn::make('event')->label(__('audit.fields.event'))->badge()->color('gray')->formatStateUsing(fn (?string $state): string => static::eventLabel($state)),
                TextColumn::make('description')->label(__('audit.fields.description'))->limit(50)->wrap(),
                TextColumn::make('causer.name')->label(__('audit.fields.causer'))->placeholder(__('audit.system'))->searchable(),
                TextColumn::make('subject_type')
                    ->label(__('audit.fields.subject'))
                    ->formatStateUsing(fn (?string $state, Activity $record): string => $state ? class_basename($state).' #'.$record->subject_id : '—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label(__('audit.fields.log_name'))
                    ->options(fn (): array => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->filter()->map(fn (string $name): string => static::logLabel($name))->all()),
                SelectFilter::make('event')
                    ->label(__('audit.fields.event'))
                    ->options(fn (): array => Activity::query()->distinct()->orderBy('event')->pluck('event', 'event')->filter()->map(fn (string $event): string => static::eventLabel($event))->all()),
                SelectFilter::make('causer_id')
                    ->label(__('audit.fields.causer'))
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $id) => $query->where('causer_type', (new User)->getMorphClass())->where('causer_id', $id))),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('audit.fields.from')),
                        DatePicker::make('until')->label(__('audit.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    TextEntry::make('created_at')->label(__('audit.fields.created_at'))->dateTime(),
                    TextEntry::make('log_name')->label(__('audit.fields.log_name'))->formatStateUsing(fn (?string $state): string => static::logLabel($state)),
                    TextEntry::make('event')->label(__('audit.fields.event'))->formatStateUsing(fn (?string $state): string => static::eventLabel($state)),
                    TextEntry::make('description')->label(__('audit.fields.description')),
                    TextEntry::make('causer.name')->label(__('audit.fields.causer'))->placeholder(__('audit.system')),
                    TextEntry::make('subject_type')->label(__('audit.fields.subject'))->formatStateUsing(fn (?string $state, Activity $record): string => $state ? class_basename($state).' #'.$record->subject_id : '—'),
                ]),
            Section::make(__('audit.fields.changes'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    KeyValueEntry::make('old')
                        ->label(__('audit.fields.old'))
                        ->state(fn (Activity $record): array => static::flatten((array) ($record->properties['old'] ?? []))),
                    KeyValueEntry::make('attributes')
                        ->label(__('audit.fields.new'))
                        ->state(fn (Activity $record): array => static::flatten((array) ($record->properties['attributes'] ?? []))),
                    KeyValueEntry::make('other')
                        ->label(__('audit.fields.properties'))
                        ->columnSpanFull()
                        ->state(fn (Activity $record): array => static::flatten(collect($record->properties ?? [])->except(['old', 'attributes'])->all())),
                ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public static function flatten(array $values): array
    {
        return collect($values)->map(fn (mixed $value): string => match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            $value === null => '—',
            default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
        })->all();
    }

    public static function logLabel(?string $name): string
    {
        $key = 'audit.logs.'.$name;

        return $name && __($key) !== $key ? __($key) : (string) $name;
    }

    public static function eventLabel(?string $event): string
    {
        $key = 'audit.events.'.$event;

        return $event && __($key) !== $key ? __($key) : (string) $event;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
