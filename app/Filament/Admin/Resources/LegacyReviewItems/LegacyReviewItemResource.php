<?php

namespace App\Filament\Admin\Resources\LegacyReviewItems;

use App\Enums\CaseType;
use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Activities\ActivityResource;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\LegacyReviewItems\Pages\ListLegacyReviewItems;
use App\Filament\Admin\Resources\LegacyReviewItems\Pages\ViewLegacyReviewItem;
use App\Models\CourtCase;
use App\Models\LegacyIdMap;
use App\Models\LegacyReviewItem;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * What the legacy importer could not map (proposal B8): nothing is dropped, every
 * problem waits here until someone checks it. Raw values are decrypted only here.
 */
class LegacyReviewItemResource extends Resource
{
    protected static ?string $model = LegacyReviewItem::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'legacy-review';

    // Albanian labels are sentences: no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('legacy.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('legacy.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('legacy.navigation');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = LegacyReviewItem::query()->whereNull('resolved_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->description(__('legacy.help'))
            ->columns([
                TextColumn::make('legacy_module')->label(__('legacy.fields.module'))->formatStateUsing(fn (string $state): string => self::moduleLabel($state))->sortable(),
                TextColumn::make('legacy_id')->label(__('legacy.fields.legacy_id'))->searchable(),
                TextColumn::make('field')->label(__('legacy.fields.field'))->searchable(),
                TextColumn::make('reason')->label(__('legacy.fields.reason'))->badge()->color(fn (string $state): string => self::reasonColor($state))->formatStateUsing(fn (string $state): string => self::reasonLabel($state)),
                TextColumn::make('value')->label(__('legacy.fields.value'))->limit(60)->wrap()->placeholder(__('legacy.personal_value'))->searchable(),
                IconColumn::make('resolved')->label(__('legacy.fields.resolved'))->state(fn (LegacyReviewItem $record): bool => $record->resolved_at !== null)->boolean(),
                TextColumn::make('created_at')->label(__('legacy.fields.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('resolved')
                    ->label(__('legacy.fields.status'))
                    ->placeholder(__('legacy.status.all'))
                    ->trueLabel(__('legacy.status.resolved'))
                    ->falseLabel(__('legacy.status.open'))
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('resolved_at'),
                        false: fn (Builder $query) => $query->whereNull('resolved_at'),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('legacy_module')->label(__('legacy.fields.module'))->options(fn (): array => collect((array) config('legacy.modules'))->mapWithKeys(fn (array $module, string $key): array => [$key => self::moduleLabel($key)])->all()),
                SelectFilter::make('reason')->label(__('legacy.fields.reason'))->options(fn (): array => collect((array) __('legacy.reasons'))->all()),
                SelectFilter::make('field')->label(__('legacy.fields.field'))->options(fn (): array => LegacyReviewItem::query()->distinct()->orderBy('field')->pluck('field', 'field')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                self::resolveAction(),
            ])
            ->toolbarActions([
                BulkAction::make('resolveSelected')
                    ->label(__('legacy.actions.resolve_selected'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->authorize(fn (): bool => auth()->user()?->can('update', new LegacyReviewItem) ?? false)
                    ->schema([Textarea::make('note')->label(__('legacy.fields.resolution_note'))->maxLength(2000)])
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            if ($record instanceof LegacyReviewItem) {
                                self::resolve($record, $data['note'] ?? null);
                            }
                        }

                        Notification::make()->title(__('legacy.actions.resolved'))->success()->send();
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('legacy_module')->label(__('legacy.fields.module'))->formatStateUsing(fn (string $state): string => self::moduleLabel($state)),
                    TextEntry::make('legacy_table')->label(__('legacy.fields.legacy_table')),
                    TextEntry::make('legacy_id')->label(__('legacy.fields.legacy_id')),
                    TextEntry::make('field')->label(__('legacy.fields.field')),
                    TextEntry::make('reason')->label(__('legacy.fields.reason'))->badge()->color(fn (string $state): string => self::reasonColor($state))->formatStateUsing(fn (string $state): string => self::reasonLabel($state)),
                    TextEntry::make('created_at')->label(__('legacy.fields.created_at'))->dateTime(),
                    TextEntry::make('resolved_at')->label(__('legacy.fields.resolved_at'))->dateTime()->placeholder(__('legacy.status.open')),
                    TextEntry::make('resolver.name')->label(__('legacy.fields.resolver'))->placeholder('—'),
                    TextEntry::make('resolution_note')->label(__('legacy.fields.resolution_note'))->placeholder('—'),
                ]),
            Section::make(__('legacy.fields.payload'))
                ->description(__('legacy.payload_help'))
                ->columnSpanFull()
                ->schema([
                    KeyValueEntry::make('payload')
                        ->hiddenLabel()
                        ->keyLabel(__('legacy.fields.field'))
                        ->valueLabel(__('legacy.fields.value'))
                        ->state(fn (LegacyReviewItem $record): array => self::flatten((array) ($record->payload ?? []))),
                ]),
        ]);
    }

    public static function resolveAction(): Action
    {
        return Action::make('resolve')
            ->label(fn (LegacyReviewItem $record): string => $record->resolved_at === null ? __('legacy.actions.resolve') : __('legacy.actions.reopen'))
            ->icon(fn (LegacyReviewItem $record): Heroicon => $record->resolved_at === null ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedArrowUturnLeft)
            ->color(fn (LegacyReviewItem $record): string => $record->resolved_at === null ? 'success' : 'gray')
            ->authorize(fn (LegacyReviewItem $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->schema(fn (LegacyReviewItem $record): array => $record->resolved_at === null
                ? [Textarea::make('note')->label(__('legacy.fields.resolution_note'))->maxLength(2000)]
                : [])
            ->action(function (LegacyReviewItem $record, array $data): void {
                if ($record->resolved_at === null) {
                    self::resolve($record, $data['note'] ?? null);
                    Notification::make()->title(__('legacy.actions.resolved'))->success()->send();
                } else {
                    $record->update(['resolved_at' => null, 'resolved_by' => null, 'resolution_note' => null]);
                }
            });
    }

    public static function openCaseAction(): Action
    {
        return Action::make('openCase')
            ->label(__('legacy.actions.open_case'))
            ->icon(Heroicon::OutlinedFolderOpen)
            ->color('gray')
            ->visible(fn (LegacyReviewItem $record): bool => self::caseOf($record) !== null)
            ->url(fn (LegacyReviewItem $record): ?string => ($case = self::caseOf($record)) === null ? null : CourtCaseResource::getUrl('view', ['record' => $case]));
    }

    public static function resolve(LegacyReviewItem $record, ?string $note): void
    {
        if ($record->resolved_at !== null || ! (auth()->user()?->can('update', $record) ?? false)) {
            return;
        }

        $record->update(['resolved_at' => now(), 'resolved_by' => auth()->id(), 'resolution_note' => filled($note) ? $note : null]);
    }

    /**
     * The imported case the item belongs to, when it can be found.
     */
    public static function caseOf(LegacyReviewItem $record): ?CourtCase
    {
        $caseId = $record->payload['case_id'] ?? null;

        if (is_numeric($caseId)) {
            return CourtCase::query()->find((int) $caseId);
        }

        $mapped = LegacyIdMap::query()
            ->where(['legacy_table' => $record->legacy_table, 'legacy_key' => $record->legacy_id, 'target_type' => 'case'])
            ->value('target_id');

        return $mapped === null ? null : CourtCase::query()->find((int) $mapped);
    }

    /**
     * The module's case type in the current language ("Penale – Korrupsion").
     */
    public static function moduleLabel(string $module): string
    {
        $type = CaseType::tryFrom((string) config("legacy.modules.{$module}.case_type"));

        return $type?->getLabel() ?? (string) (config("legacy.modules.{$module}.label") ?? $module);
    }

    public static function reasonLabel(string $reason): string
    {
        $key = 'legacy.reasons.'.$reason;

        return __($key) !== $key ? __($key) : $reason;
    }

    public static function reasonColor(string $reason): string
    {
        return match ($reason) {
            'import_failed', 'unknown_reference' => 'danger',
            'conflicting_values', 'inconsistent_answers', 'stage_chronology', 'invalid_date', 'invalid_number' => 'warning',
            'new_reference_value' => 'info',
            default => 'gray',
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public static function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $label = $prefix === '' && __('legacy.payload_keys.'.$key) !== 'legacy.payload_keys.'.$key ? __('legacy.payload_keys.'.$key) : (string) $key;
            $name = $prefix === '' ? $label : $prefix.'.'.$key;

            if (is_array($value) && $value !== [] && count($value) <= 50 && ! array_is_list($value)) {
                $flat += self::flatten($value, $name);
            } else {
                $flat += ActivityResource::flatten([$name => $value]);
            }
        }

        return $flat;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegacyReviewItems::route('/'),
            'view' => ViewLegacyReviewItem::route('/{record}'),
        ];
    }
}
