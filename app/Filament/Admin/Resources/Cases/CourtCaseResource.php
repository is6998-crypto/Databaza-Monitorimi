<?php

namespace App\Filament\Admin\Resources\Cases;

use App\Enums\NavigationGroup;
use App\Filament\Admin\Resources\Cases\Pages\CreateCourtCase;
use App\Filament\Admin\Resources\Cases\Pages\EditCourtCase;
use App\Filament\Admin\Resources\Cases\Pages\ListCourtCases;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Filament\Admin\Resources\Cases\RelationManagers\ActivitiesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\ChargesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\HearingsRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\MeasuresRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\PartiesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\SentencesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\StagesRelationManager;
use App\Filament\Admin\Resources\Cases\RelationManagers\TransitionsRelationManager;
use App\Filament\Admin\Resources\Cases\Schemas\CourtCaseForm;
use App\Filament\Admin\Resources\Cases\Schemas\CourtCaseInfolist;
use App\Filament\Admin\Resources\Cases\Tables\CourtCasesTable;
use App\Models\CourtCase;
use App\Models\User;
use App\Services\Search\CaseSearch;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use UnitEnum;

class CourtCaseResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = CourtCase::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'cases';

    protected static ?string $recordTitleAttribute = 'court_case_number';

    public static function getModelLabel(): string
    {
        return __('cases.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cases.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof CourtCase ? $record->display_label : static::getModelLabel();
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['court_case_number', 'indictment_number', 'police_case_number'];
    }

    /**
     * pg_trgm fuzzy search on numbers, judge and offence, plus exact party-name tokens
     * through the blind index (App\Services\Search\CaseSearch).
     *
     * @return Collection<int, GlobalSearchResult>
     */
    public static function getGlobalSearchResults(string $search): Collection
    {
        $query = CaseSearch::rank(CaseSearch::apply(static::getGlobalSearchEloquentQuery()->with(['court']), $search), $search);

        return $query
            ->limit(static::getGlobalSearchResultsLimit())
            ->get()
            ->map(fn (Model $record): ?GlobalSearchResult => $record instanceof CourtCase ? new GlobalSearchResult(
                title: $record->display_label,
                url: static::getUrl('view', ['record' => $record]),
                details: [
                    __('cases.fields.type') => $record->type->getLabel(),
                    __('cases.fields.status') => $record->status->getLabel(),
                ],
            ) : null)
            ->filter();
    }

    public static function form(Schema $schema): Schema
    {
        return CourtCaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CourtCaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourtCasesTable::configure($table);
    }

    /**
     * Case workspace tabs: Overview · Parties & Charges · Stages · Hearings ·
     * Measures & Sentences · Documents · History (proposal B6.4).
     */
    public static function getRelations(): array
    {
        return [
            RelationGroup::make(__('cases.tabs.parties'), [PartiesRelationManager::class, ChargesRelationManager::class])->icon(Heroicon::OutlinedUsers),
            StagesRelationManager::class,
            HearingsRelationManager::class,
            RelationGroup::make(__('cases.tabs.measures'), [MeasuresRelationManager::class, SentencesRelationManager::class])->icon(Heroicon::OutlinedScale),
            AttachmentsRelationManager::class,
            RelationGroup::make(__('cases.tabs.history'), [TransitionsRelationManager::class, ActivitiesRelationManager::class])->icon(Heroicon::OutlinedClock),
        ];
    }

    /**
     * Monitors only see cases in their courts (plus their own); trashed rows only for admins.
     *
     * @return Builder<CourtCase>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<CourtCase> $query */
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User && $user->isSuperAdmin()) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourtCases::route('/'),
            'create' => CreateCourtCase::route('/create'),
            'view' => ViewCourtCase::route('/{record}'),
            'edit' => EditCourtCase::route('/{record}/edit'),
        ];
    }
}
