<?php

namespace App\Filament\Admin\Resources\Reference\CriminalOffences;

use App\Filament\Admin\Resources\Reference\CriminalOffences\Pages\ManageCriminalOffences;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Category;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\ReferenceModel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CriminalOffenceResource extends ReferenceResource
{
    protected static ?string $model = CriminalOffence::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'reference/criminal-offences';

    public static function key(): string
    {
        return 'criminal_offences';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('criminal_code_id')->label(__('reference.fields.criminal_code'))->relationship('criminalCode', 'code')->searchable()->preload()->required(),
            TextInput::make('article_number')->label(__('reference.fields.article_number'))->required()->maxLength(32),
            TextInput::make('chapter')->label(__('reference.fields.chapter'))->maxLength(255),
            Select::make('category_id')->label(__('reference.fields.category'))->relationship('category', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('criminalCode.code')->label(__('reference.fields.criminal_code'))->sortable()->badge(),
            TextColumn::make('article_number')->label(__('reference.fields.article_number'))->searchable()->sortable(),
            TextColumn::make('category.code')->label(__('reference.fields.category_code'))->badge()->sortable()->toggleable(),
            TextColumn::make('category.name_sq')->getStateUsing(fn ($record): ?string => $record->category?->label())->label(__('reference.fields.category'))->toggleable(),
        ];
    }

    protected static function extraFilters(): array
    {
        return [
            SelectFilter::make('criminal_code_id')->label(__('reference.fields.criminal_code'))->relationship('criminalCode', 'code'),
            SelectFilter::make('category_id')->label(__('reference.fields.category'))->relationship('category', 'name_sq')->getOptionLabelFromRecordUsing(fn (ReferenceModel $record): string => $record->label())->searchable()->preload(),
        ];
    }

    public static function csvColumns(): array
    {
        return [
            ...parent::csvColumns(),
            'criminal_code' => ['rules' => ['required', 'string'], 'resolve' => static::resolver(CriminalCode::class, ['code']), 'attribute' => 'criminal_code_id'],
            'article_number' => ['rules' => ['required', 'string', 'max:32']],
            'chapter' => ['rules' => ['nullable', 'string', 'max:255']],
            'category' => ['rules' => ['nullable', 'string'], 'resolve' => static::resolver(Category::class, ['code', 'name_sq', 'name_en']), 'attribute' => 'category_id'],
        ];
    }

    public static function csvMatchOn(): array
    {
        return ['criminal_code_id', 'article_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCriminalOffences::route('/'),
        ];
    }
}
