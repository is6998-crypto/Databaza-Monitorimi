<?php

namespace App\Filament\Admin\Resources\Publications;

use App\Enums\NavigationGroup;
use App\Enums\PublicationType;
use App\Filament\Admin\Resources\Publications\Pages\ManagePublications;
use App\Models\Publication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Reports & publications shown on the public portal (Editors publish them).
 */
class PublicationResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Publication::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Portal;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'publications';

    public static function getModelLabel(): string
    {
        return __('publications.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('publications.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                TextInput::make('title_sq')->label(__('publications.fields.title_sq'))->required()->maxLength(255)->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set, ?Publication $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                TextInput::make('title_en')->label(__('publications.fields.title_en'))->maxLength(255),
                TextInput::make('slug')->label(__('publications.fields.slug'))->required()->alphaDash()->maxLength(255)->unique(ignoreRecord: true),
                Select::make('type')->label(__('publications.fields.type'))->options(PublicationType::options())->required()->default(PublicationType::Report->value),
                Textarea::make('summary_sq')->label(__('publications.fields.summary_sq'))->rows(4),
                Textarea::make('summary_en')->label(__('publications.fields.summary_en'))->rows(4),
                FileUpload::make('file_path')
                    ->label(__('publications.fields.file'))
                    ->disk('local')
                    ->directory('publications')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(51200)
                    ->columnSpanFull(),
                DatePicker::make('published_on')->label(__('publications.fields.published_on')),
                Toggle::make('is_published')->label(__('publications.fields.is_published'))->helperText(__('publications.help.is_published')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_on', 'desc')
            ->columns([
                TextColumn::make('title_sq')->label(__('publications.fields.title_sq'))->searchable()->wrap(),
                TextColumn::make('type')->label(__('publications.fields.type'))->badge(),
                TextColumn::make('published_on')->label(__('publications.fields.published_on'))->date()->sortable(),
                IconColumn::make('is_published')->label(__('publications.fields.is_published'))->boolean(),
                IconColumn::make('file_path')->label(__('publications.fields.file'))->boolean()->state(fn (Publication $record): bool => filled($record->file_path)),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('publications.preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Publication $record): string => route('portal.publications.show', ['locale' => 'sq', 'slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (Publication $record): bool => $record->is_published),
                EditAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'file_disk' => filled($data['file_path'] ?? null) ? 'local' : null]),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePublications::route('/'),
        ];
    }
}
