<?php

namespace App\Filament\Admin\Resources\Cases\Schemas;

use App\Enums\CaseType;
use App\Enums\CivilOutcome;
use App\Enums\ClaimType;
use App\Enums\LegalRemedyType;
use App\Enums\PlaintiffType;
use App\Enums\Representation;
use App\Enums\SecondInstanceOutcome;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CourtCase;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\Department;
use App\Models\Reference\Division;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\ProsecutionOffice;
use App\Models\Reference\Prosecutor;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CourtCaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::classification(),
                static::numbers(),
                static::assignment(),
                static::administrativeDetails(),
                static::civilDetails(),
            ]);
    }

    public static function isCriminal(Get $get): bool
    {
        $type = $get('type');
        $type = $type instanceof CaseType ? $type : CaseType::tryFrom((string) $type);

        return $type?->isCriminal() ?? false;
    }

    protected static function classification(): Section
    {
        return Section::make(__('cases.sections.classification'))
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                Select::make('type')
                    ->label(__('cases.fields.type'))
                    ->options(CaseType::options())
                    ->required()
                    ->live(),
                RefSelect::make('court_id', Court::class)
                    ->label(__('cases.fields.court'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('court_branch_id', null);
                    }),
                RefSelect::make('court_branch_id', CourtBranch::class, fn (Builder $query, Get $get) => $query->where('court_id', $get('court_id')))
                    ->label(__('cases.fields.court_branch'))
                    ->visible(fn (Get $get): bool => filled($get('court_id')) && CourtBranch::query()->where('court_id', $get('court_id'))->exists()),
                RefSelect::make('department_id', Department::class)
                    ->label(__('cases.fields.department'))
                    ->live(),
                RefSelect::make('division_id', Division::class, fn (Builder $query, Get $get) => $query->when($get('department_id'), fn (Builder $query, $id) => $query->where(fn (Builder $query) => $query->where('department_id', $id)->orWhereNull('department_id'))))
                    ->label(__('cases.fields.division')),
                RefSelect::make('judge_id', Judge::class, fn (Builder $query, Get $get) => $query->when($get('court_id'), fn (Builder $query, $id) => $query->where('court_id', $id)))
                    ->label(__('cases.fields.judge')),
                RefSelect::make('prosecution_office_id', ProsecutionOffice::class)
                    ->label(__('cases.fields.prosecution_office'))
                    ->live()
                    ->visible(fn (Get $get): bool => static::isCriminal($get)),
                RefSelect::make('prosecutor_id', Prosecutor::class, fn (Builder $query, Get $get) => $query->when($get('prosecution_office_id'), fn (Builder $query, $id) => $query->where('prosecution_office_id', $id)))
                    ->label(__('cases.fields.prosecutor'))
                    ->visible(fn (Get $get): bool => static::isCriminal($get)),
            ]);
    }

    protected static function numbers(): Section
    {
        return Section::make(__('cases.sections.numbers'))
            ->columns(['default' => 1, 'md' => 3])
            ->schema([
                TextInput::make('court_case_number')
                    ->label(__('cases.fields.court_case_number'))
                    ->maxLength(64)
                    ->live(onBlur: true),
                TextInput::make('indictment_number')
                    ->label(__('cases.fields.indictment_number'))
                    ->maxLength(64)
                    ->visible(fn (Get $get): bool => static::isCriminal($get)),
                TextInput::make('police_case_number')
                    ->label(__('cases.fields.police_case_number'))
                    ->maxLength(64)
                    ->visible(fn (Get $get): bool => static::isCriminal($get)),
                Callout::make(__('cases.duplicate.heading'))
                    ->description(__('cases.duplicate.description'))
                    ->warning()
                    ->columnSpanFull()
                    ->visible(fn (Get $get, ?CourtCase $record): bool => static::duplicate($get, $record) !== null)
                    ->actions([
                        Action::make('openDuplicate')
                            ->label(__('cases.duplicate.open'))
                            ->url(fn (Get $get, ?CourtCase $record): ?string => ($duplicate = static::duplicate($get, $record)) ? CourtCaseResource::getUrl('view', ['record' => $duplicate]) : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }

    /**
     * Existing case with the same number in the same court (warning only, never blocking).
     */
    public static function duplicate(Get $get, ?CourtCase $record): ?CourtCase
    {
        $number = trim((string) $get('court_case_number'));
        $courtId = $get('court_id');

        if ($number === '' || blank($courtId)) {
            return null;
        }

        $user = auth()->user();
        $query = CourtCase::duplicatesOf((int) $courtId, $number, $record?->getKey());

        if ($user instanceof User) {
            $query->visibleTo($user);
        }

        return $query->first();
    }

    protected static function assignment(): Section
    {
        return Section::make(__('cases.sections.assignment'))
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                Select::make('assigned_monitor_id')
                    ->label(__('cases.fields.assigned_monitor'))
                    ->options(fn (): array => Monitor::selectOptions())
                    ->searchable()
                    ->default(fn (): ?int => auth()->user() instanceof User ? auth()->user()->monitor?->id : null)
                    ->disabled(fn (): bool => ! Gate::allows('assignMonitor', CourtCase::class))
                    ->dehydrated(),
                Textarea::make('summary')
                    ->label(__('cases.fields.summary'))
                    ->helperText(__('cases.help.summary'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    protected static function administrativeDetails(): Section
    {
        return Section::make(__('cases.sections.administrative'))
            ->relationship('administrativeDetails')
            ->visible(fn (Get $get): bool => $get('type') === CaseType::Administrative->value || $get('type') === CaseType::Administrative)
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                Textarea::make('first_instance_decision')->label(__('cases.admin.first_instance_decision'))->rows(2)->columnSpanFull(),
                DatePicker::make('first_instance_decision_date')->label(__('cases.admin.first_instance_decision_date')),
                ToggleButtons::make('decision_reasoned')->label(__('cases.admin.decision_reasoned'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped(),
                DatePicker::make('decision_received_date')->label(__('cases.admin.decision_received_date'))->afterOrEqual('first_instance_decision_date'),
                Select::make('legal_remedy_type')->label(__('cases.admin.legal_remedy_type'))->options(LegalRemedyType::options())->live(),
                DatePicker::make('legal_remedy_date')->label(__('cases.admin.legal_remedy_date'))
                    ->visible(fn (Get $get): bool => filled($get('legal_remedy_type')) && $get('legal_remedy_type') !== LegalRemedyType::None->value),
                Select::make('second_instance_outcome')->label(__('cases.admin.second_instance_outcome'))->options(SecondInstanceOutcome::options()),
                DatePicker::make('second_instance_decision_date')->label(__('cases.admin.second_instance_decision_date'))->afterOrEqual('first_instance_decision_date'),
                ToggleButtons::make('conflict_filed')->label(__('cases.admin.conflict_filed'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped()->default(false)->live(),
                DatePicker::make('conflict_filed_date')->label(__('cases.admin.conflict_filed_date'))->visible(fn (Get $get): bool => (bool) $get('conflict_filed')),
                Select::make('plaintiff_type')->label(__('cases.admin.plaintiff_type'))->options(PlaintiffType::options()),
                TextInput::make('respondent_body')->label(__('cases.admin.respondent_body'))->maxLength(255),
                Select::make('representation')->label(__('cases.admin.representation'))->options(Representation::options()),
                DatePicker::make('first_review_date')->label(__('cases.admin.first_review_date')),
                DatePicker::make('last_review_date')->label(__('cases.admin.last_review_date'))->afterOrEqual('first_review_date'),
            ]);
    }

    protected static function civilDetails(): Section
    {
        return Section::make(__('cases.sections.civil'))
            ->relationship('civilDetails')
            ->visible(fn (Get $get): bool => in_array($get('type'), [CaseType::Civil->value, CaseType::Economic->value, CaseType::Civil, CaseType::Economic], true))
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                Select::make('claim_type')->label(__('cases.civil.claim_type'))->options(ClaimType::options()),
                TextInput::make('claim_value_eur')->label(__('cases.civil.claim_value_eur'))->numeric()->minValue(0)->prefix('€'),
                DatePicker::make('filed_date')->label(__('cases.civil.filed_date')),
                Select::make('plaintiff_type')->label(__('cases.civil.plaintiff_type'))->options(PlaintiffType::options()),
                Select::make('respondent_type')->label(__('cases.civil.respondent_type'))->options(PlaintiffType::options()),
                Select::make('representation')->label(__('cases.civil.representation'))->options(Representation::options()),
                ToggleButtons::make('legal_aid')->label(__('cases.civil.legal_aid'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped(),
                ToggleButtons::make('mediation_attempted')->label(__('cases.civil.mediation_attempted'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped(),
                Select::make('outcome')->label(__('cases.civil.outcome'))->options(CivilOutcome::options()),
                DatePicker::make('decision_date')->label(__('cases.civil.decision_date'))->afterOrEqual('filed_date'),
            ]);
    }
}
