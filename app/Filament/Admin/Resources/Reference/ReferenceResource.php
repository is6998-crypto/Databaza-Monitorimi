<?php

namespace App\Filament\Admin\Resources\Reference;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Actions\MergeDuplicatesBulkAction;
use App\Models\Reference\ReferenceModel;
use App\Services\Reference\CsvImporter;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Shared Filament resource for every reference table: bilingual names (Serbian-ready),
 * is_active flag, soft delete/restore, search, merge duplicates and CSV import.
 */
abstract class ReferenceResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ReferenceData;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Key in lang/{locale}/reference.php, e.g. "courts".
     */
    abstract public static function key(): string;

    /**
     * @return class-string<ReferenceModel>
     */
    public static function referenceModel(): string
    {
        /** @var class-string<ReferenceModel> $model */
        $model = static::getModel();

        return $model;
    }

    public static function getModelLabel(): string
    {
        return __('reference.'.static::key().'.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reference.'.static::key().'.plural');
    }

    /**
     * Albanian does not capitalise every word, so keep the translated plural as written.
     */
    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return static::referenceModel()::labelColumn();
    }

    // ------------------------------------------------------------------ form

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                ...static::nameFields(),
                ...static::extraFormFields(),
                Toggle::make('is_active')->label(__('reference.fields.is_active'))->default(true)->columnSpanFull(),
            ]);
    }

    /**
     * @return list<Component|Field>
     */
    protected static function nameFields(): array
    {
        $model = static::referenceModel();

        if (! $model::isTranslatable()) {
            return [
                TextInput::make('name')->label(__('reference.fields.name'))->required()->maxLength(255)->columnSpanFull(),
            ];
        }

        $prefix = $model::labelPrefix();

        return [
            TextInput::make("{$prefix}_sq")->label(__("reference.fields.{$prefix}_sq"))->required()->maxLength(255),
            TextInput::make("{$prefix}_en")->label(__("reference.fields.{$prefix}_en"))->maxLength(255),
            TextInput::make("{$prefix}_sr")->label(__("reference.fields.{$prefix}_sr"))->maxLength(255)->helperText(__('reference.help.serbian')),
        ];
    }

    /**
     * @return list<Component|Field>
     */
    protected static function extraFormFields(): array
    {
        return [];
    }

    // ------------------------------------------------------------------ table

    public static function table(Table $table): Table
    {
        $model = static::referenceModel();

        return $table
            ->columns([
                ...static::labelColumns(),
                ...static::extraColumns(),
                IconColumn::make('is_active')->label(__('reference.fields.is_active'))->boolean()->sortable(),
                TextColumn::make('updated_at')->label(__('reference.fields.updated_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort($model::labelColumn())
            ->filters([
                TernaryFilter::make('is_active')->label(__('reference.fields.is_active')),
                ...static::extraFilters(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    MergeDuplicatesBulkAction::make($model),
                    BulkAction::make('deactivate')
                        ->label(__('reference.actions.deactivate'))
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->requiresConfirmation()
                        ->visible(fn (): bool => Gate::allows('create', $model))
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof ReferenceModel && Gate::allows('update', $record)) {
                                    $record->update(['is_active' => false]);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return list<Column>
     */
    protected static function labelColumns(): array
    {
        $model = static::referenceModel();

        if (! $model::isTranslatable()) {
            return [
                TextColumn::make('name')->label(__('reference.fields.name'))->searchable()->sortable(),
            ];
        }

        $prefix = $model::labelPrefix();

        return [
            TextColumn::make("{$prefix}_sq")->label(__("reference.fields.{$prefix}_sq"))->searchable()->sortable()->wrap(),
            TextColumn::make("{$prefix}_en")->label(__("reference.fields.{$prefix}_en"))->searchable()->sortable()->toggleable()->wrap(),
            TextColumn::make("{$prefix}_sr")->label(__("reference.fields.{$prefix}_sr"))->searchable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return list<Column>
     */
    protected static function extraColumns(): array
    {
        return [];
    }

    /**
     * @return list<BaseFilter>
     */
    protected static function extraFilters(): array
    {
        return [];
    }

    /**
     * Includes soft-deleted rows so the "trashed" filter and restore actions work.
     *
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    // ------------------------------------------------------------------ CSV import

    /**
     * @return array<string, array{rules?: list<mixed>|string, resolve?: Closure(string): (int|null), attribute?: string}>
     */
    public static function csvColumns(): array
    {
        $model = static::referenceModel();

        if (! $model::isTranslatable()) {
            return ['name' => ['rules' => ['required', 'string', 'max:255']]];
        }

        $prefix = $model::labelPrefix();

        return [
            "{$prefix}_sq" => ['rules' => ['required', 'string', 'max:255']],
            "{$prefix}_en" => ['rules' => ['nullable', 'string', 'max:255']],
            "{$prefix}_sr" => ['rules' => ['nullable', 'string', 'max:255']],
        ];
    }

    /**
     * @return list<string>
     */
    public static function csvMatchOn(): array
    {
        $model = static::referenceModel();

        return [$model::isTranslatable() ? $model::labelPrefix().'_sq' : 'name'];
    }

    public static function csvImporter(): CsvImporter
    {
        return new CsvImporter(static::referenceModel(), [...static::csvColumns(), 'is_active' => ['rules' => ['nullable', 'string']]], static::csvMatchOn());
    }

    /**
     * Resolves a related reference id from a code or a name in a CSV cell.
     *
     * @param  class-string<ReferenceModel>  $model
     * @param  list<string>  $columns
     * @return Closure(string): (int|null)
     */
    protected static function resolver(string $model, array $columns): Closure
    {
        return function (string $value) use ($model, $columns): ?int {
            $needle = mb_strtolower(trim($value));

            foreach ($columns as $column) {
                $id = $model::query()->whereRaw("lower(trim({$column})) = ?", [$needle])->value('id');

                if ($id !== null) {
                    return (int) $id;
                }
            }

            return null;
        };
    }
}
