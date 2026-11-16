<?php

namespace App\Filament\Admin\Resources\HearingReports;

use App\Enums\NavigationGroup;
use App\Enums\ReportStatus;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\HearingReports\Pages\CreateHearingReport;
use App\Filament\Admin\Resources\HearingReports\Pages\EditHearingReport;
use App\Filament\Admin\Resources\HearingReports\Pages\ListHearingReports;
use App\Filament\Admin\Resources\HearingReports\Pages\ViewHearingReport;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Filament\Admin\Support\CaseColumn;
use App\Livewire\Admin\ReportComments;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\Reference\Court;
use App\Models\User;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * "Hearing reports": for each court session the monitor writes what happened; an editor reads it, edits it and
 * comments on it. Internal only (the text may name people).
 */
class HearingReportResource extends Resource
{
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = HearingReport::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Cases;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'hearing-reports';

    public static function getModelLabel(): string
    {
        return __('reports.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reports.plural');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof HearingReport ? $record->display_label : static::getModelLabel();
    }

    /**
     * What waits for this person: reports to review for an editor, reports sent back for a monitor.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $query = static::getEloquentQuery();

        $count = $user->checkPermissionTo(Permissions::REVIEW)
            ? $query->awaitingReview()->count()
            : $query->where('status', ReportStatus::Returned->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * "12.03.2026 · PKR.nr.12/24 · Gjykata Themelore e Prishtinës".
     */
    public static function hearingLabel(Hearing $hearing): string
    {
        return $hearing->hearing_date->format((string) config('courtwatch.date_format')).' · '.$hearing->case->display_label;
    }

    /**
     * Hearings this person may write a report for: an editor any hearing, a monitor the ones they monitored.
     *
     * @return Builder<Hearing>
     */
    public static function reportableHearings(): Builder
    {
        $user = auth()->user();

        return Hearing::query()
            ->with('case.court')
            ->when($user instanceof User, fn (Builder $query) => $query->visibleTo($user))
            ->when($user instanceof User && $user->isMonitorOnly(), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('hearings.created_by', $user->id)
                ->orWhere('hearings.monitor_id', $user->monitor->id ?? 0)))
            ->latest('hearing_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('reports.sections.hearing'))
                    ->schema([
                        Select::make('hearing_id')
                            ->label(__('reports.fields.hearing'))
                            ->helperText(fn (string $operation): ?string => $operation === 'create' ? __('reports.help.hearing') : null)
                            ->options(fn (?HearingReport $record): array => static::reportableHearings()
                                ->where(fn (Builder $query) => $query->whereDoesntHave('report')->when($record, fn (Builder $query) => $query->orWhereKey($record->hearing_id)))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Hearing $hearing): array => [$hearing->id => static::hearingLabel($hearing)])
                                ->all())
                            ->getSearchResultsUsing(fn (string $search): array => static::reportableHearings()
                                ->whereDoesntHave('report')
                                ->whereHas('case', fn (Builder $case) => $case
                                    ->where('court_case_number', 'ilike', "%{$search}%")
                                    ->orWhere('indictment_number', 'ilike', "%{$search}%"))
                                ->limit(25)
                                ->get()
                                ->mapWithKeys(fn (Hearing $hearing): array => [$hearing->id => static::hearingLabel($hearing)])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => ($hearing = Hearing::query()->with('case.court')->find($value)) ? static::hearingLabel($hearing) : null)
                            ->searchable()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => __('reports.errors.exists')])
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),
                    ]),

                Section::make(__('reports.sections.report'))
                    ->description(__('reports.help.report'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('reports.fields.title'))
                            ->maxLength(200),
                        RichEditor::make('body')
                            ->label(__('reports.fields.body'))
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['link'],
                                ['undo', 'redo'],
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 22rem'])
                            ->maxLength(60000)
                            ->required(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('reports.sections.hearing'))
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('hearing.hearing_date')
                            ->label(__('hearings.fields.hearing_date'))
                            ->date()
                            ->url(fn (HearingReport $record): ?string => Gate::allows('view', $record->hearing) ? HearingResource::getUrl('view', ['record' => $record->hearing_id]) : null)
                            ->color('primary'),
                        TextEntry::make('case')
                            ->label(__('hearings.fields.case'))
                            ->state(fn (HearingReport $record): string => $record->hearing->case->display_label)
                            ->url(fn (HearingReport $record): ?string => Gate::allows('view', $record->hearing->case) ? CourtCaseResource::getUrl('view', ['record' => $record->hearing->case_id]) : null)
                            ->color('primary'),
                        TextEntry::make('hearing.hearing_type')->label(__('hearings.fields.hearing_type'))->badge()->placeholder('—'),
                        TextEntry::make('status')->label(__('reports.fields.status'))->badge(),
                        TextEntry::make('author.name')->label(__('reports.fields.author'))->placeholder('—'),
                        TextEntry::make('updated_at')->label(__('reports.fields.updated_at'))->dateTime(),
                        TextEntry::make('submitted_at')->label(__('reports.fields.submitted_at'))->dateTime()->placeholder('—'),
                        TextEntry::make('reviewer.name')->label(__('reports.fields.reviewer'))->placeholder('—'),
                    ]),

                Section::make(fn (HearingReport $record): string => filled($record->title) ? (string) $record->title : __('reports.sections.report'))
                    ->description(fn (HearingReport $record): string => trans_choice('reports.words', $record->wordCount(), ['count' => $record->wordCount()]))
                    ->extraAttributes(['class' => 'cw-report'])
                    ->schema([
                        TextEntry::make('body')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->placeholder(__('reports.empty_body')),
                    ]),

                Section::make(__('reports.comments.title'))
                    ->description(__('reports.comments.description'))
                    ->schema([
                        Livewire::make(ReportComments::class, fn (HearingReport $record): array => ['reportId' => $record->id])
                            ->key('report-comments'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['hearing.case.court', 'author'])->withCount('comments'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('hearing.hearing_date')->label(__('hearings.fields.hearing_date'))->date()->sortable(),
                CaseColumn::make('hearing_id', fn (Model $record): ?CourtCase => $record instanceof HearingReport ? $record->hearing->case : null),
                TextColumn::make('title')->label(__('reports.fields.title'))->placeholder('—')->wrap()->limit(70),
                TextColumn::make('author.name')->label(__('reports.fields.author'))->placeholder('—')->toggleable(),
                TextColumn::make('status')->label(__('reports.fields.status'))->badge()->sortable(),
                TextColumn::make('comments_count')->label(__('reports.fields.comments'))->numeric()->alignEnd()->sortable(),
                TextColumn::make('updated_at')->label(__('reports.fields.updated_at'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('reports.fields.status'))->options(ReportStatus::options())->multiple(),
                SelectFilter::make('court')
                    ->label(__('cases.fields.court'))
                    ->options(fn (): array => Court::selectOptions(false))
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $court) => $query->whereHas('hearing.case', fn (Builder $case) => $case->where('court_id', $court)))),
                TernaryFilter::make('mine')
                    ->label(__('reports.filters.mine'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('hearing_reports.created_by', auth()->id()),
                        false: fn (Builder $query) => $query,
                    ),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(fn (Action $action): Action => $action->button()->outlined())
            ->filtersFormColumns(['default' => 1, 'sm' => 3])
            ->recordActions([
                ViewAction::make()->label(__('cases.actions.open')),
                EditAction::make(),
            ])
            ->recordUrl(fn (HearingReport $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('reports.empty.heading'))
            ->emptyStateDescription(__('reports.empty.description'))
            ->emptyStateIcon(Heroicon::OutlinedDocumentText);
    }

    /**
     * Monitors only see their own reports.
     *
     * @return Builder<HearingReport>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<HearingReport> $query */
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHearingReports::route('/'),
            'create' => CreateHearingReport::route('/create'),
            'view' => ViewHearingReport::route('/{record}'),
            'edit' => EditHearingReport::route('/{record}/edit'),
        ];
    }
}
