<?php

namespace App\Filament\Admin\Resources\ExportLogs;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\ExportLogs\Pages\ListExportLogs;
use App\Models\ExportLog;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every export (staff, researcher, API, open data) — read-only.
 */
class ExportLogResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = ExportLog::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Analytics;

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'export-logs';

    public static function getModelLabel(): string
    {
        return __('exports.log.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('exports.log.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('audit.fields.created_at'))->dateTime()->sortable(),
                TextColumn::make('user.name')->label(__('audit.fields.causer'))->placeholder(__('exports.log.anonymous')),
                TextColumn::make('context')->label(__('exports.log.context'))->badge()->formatStateUsing(fn (string $state): string => __('exports.contexts.'.$state)),
                TextColumn::make('resource')->label(__('exports.log.resource')),
                TextColumn::make('format')->label(__('exports.fields.format'))->badge()->color('gray'),
                TextColumn::make('row_count')->label(__('exports.log.rows'))->numeric()->alignEnd(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('context')->label(__('exports.log.context'))->options(fn (): array => collect([ExportLog::CONTEXT_ADMIN, ExportLog::CONTEXT_RESEARCHER, ExportLog::CONTEXT_API, ExportLog::CONTEXT_PUBLIC])->mapWithKeys(fn (string $context): array => [$context => __('exports.contexts.'.$context)])->all()),
                SelectFilter::make('resource')->label(__('exports.log.resource'))->options(fn (): array => ExportLog::query()->distinct()->pluck('resource', 'resource')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExportLogs::route('/'),
        ];
    }
}
