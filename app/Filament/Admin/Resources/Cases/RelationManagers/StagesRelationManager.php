<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\Appellant;
use App\Enums\StageType;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Reference\Applicant;
use App\Models\Reference\Prosecutor;
use App\Services\StageChronology;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Stages timeline: one row per milestone with a date and an outcome. Dates must be chronological.
 */
class StagesRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'stages';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedQueueList;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.tabs.stages');
    }

    public static function stage(mixed $state): ?StageType
    {
        return $state instanceof StageType ? $state : StageType::tryFrom((string) $state);
    }

    public function form(Schema $schema): Schema
    {
        /** @var CourtCase $case */
        $case = $this->getOwnerRecord();

        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Select::make('stage')
                    ->label(__('cases.stages.stage'))
                    ->options(function (?CaseStage $record) use ($case): array {
                        $taken = $case->stages()->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))->pluck('stage')->map(fn ($stage) => $stage instanceof StageType ? $stage->value : $stage)->all();

                        return array_diff_key(StageType::options(), array_flip($taken));
                    })
                    ->required()
                    ->live(),
                DatePicker::make('date')
                    ->label(__('cases.stages.date'))
                    ->rules([
                        fn (Get $get, ?CaseStage $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record, $case): void {
                            $stage = static::stage($get('stage'));

                            if ($stage === null || blank($value)) {
                                return;
                            }

                            if ($error = StageChronology::check($case, $stage, (string) $value, $record?->getKey())) {
                                $fail($error);
                            }
                        },
                    ]),
                Select::make('outcome')
                    ->label(__('cases.stages.outcome'))
                    ->options(fn (Get $get): array => ($stage = static::stage($get('stage'))) ? StageType::optionsForOutcome($stage) : [])
                    ->visible(fn (Get $get): bool => static::stage($get('stage'))?->hasOutcome() ?? false),
                RefSelect::make('applicant_id', Applicant::class)
                    ->label(__('cases.stages.applicant'))
                    ->visible(fn (Get $get): bool => static::stage($get('stage'))?->needsApplicant() ?? false),
                RefSelect::make('prosecutor_id', Prosecutor::class)
                    ->label(__('cases.stages.prosecutor'))
                    ->visible(fn (Get $get): bool => static::stage($get('stage'))?->needsProsecutor() ?? false),
                Toggle::make('is_direct_indictment')
                    ->label(__('cases.stages.direct_indictment'))
                    ->visible(fn (Get $get): bool => static::stage($get('stage'))?->canBeDirectIndictment() ?? false),
                Select::make('appellant')
                    ->label(__('cases.stages.appellant'))
                    ->options(Appellant::options())
                    ->visible(fn (Get $get): bool => static::stage($get('stage'))?->needsAppellant() ?? false),
                Textarea::make('notes')->label(__('cases.fields.notes'))->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $order = collect(StageType::cases())
            ->map(fn (StageType $stage, int $index): string => "WHEN '{$stage->value}' THEN {$index}")
            ->implode(' ');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['applicant', 'prosecutor'])->orderByRaw("CASE stage {$order} END"))
            ->paginated(false)
            ->columns([
                TextColumn::make('stage')->label(__('cases.stages.stage'))->weight('medium')->icon(Heroicon::OutlinedFlag),
                TextColumn::make('date')->label(__('cases.stages.date'))->date()->placeholder('—'),
                TextColumn::make('days_since_previous')
                    ->label(__('cases.stages.days_since_previous'))
                    ->state(function (CaseStage $record): ?int {
                        if ($record->date === null) {
                            return null;
                        }

                        $previous = DB::table('case_stages')
                            ->where('case_id', $record->case_id)
                            ->whereNotNull('date')
                            ->where('date', '<=', $record->date->toDateString())
                            ->where('id', '!=', $record->id)
                            ->max('date');

                        return $previous ? (int) $record->date->diffInDays($previous, true) : null;
                    })
                    ->placeholder('—')
                    ->alignEnd(),
                TextColumn::make('outcome')->label(__('cases.stages.outcome'))->badge()->placeholder('—'),
                TextColumn::make('details')
                    ->label(__('cases.stages.details'))
                    ->state(fn (CaseStage $record): string => collect([
                        $record->applicant?->label(),
                        $record->prosecutor?->name,
                        $record->appellant?->getLabel(),
                        $record->is_direct_indictment ? __('cases.stages.direct_indictment') : null,
                    ])->filter()->implode(' · '))
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('cases.stages.add')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('cases.stages.empty'));
    }
}
