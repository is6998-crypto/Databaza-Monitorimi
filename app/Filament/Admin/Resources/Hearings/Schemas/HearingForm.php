<?php

namespace App\Filament\Admin\Resources\Hearings\Schemas;

use App\Enums\DelayCause;
use App\Enums\HearingType;
use App\Enums\Participant;
use App\Enums\PartyRole;
use App\Enums\PhoneUser;
use App\Enums\PostponementReason;
use App\Enums\Recording;
use App\Enums\Venue;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Reference\Court;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\Prosecutor;
use App\Models\User;
use App\Rules\NotInFuture;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Hearing observation form. Case data (court, judge, prosecutor, parties) is pre-filled from
 * the case; only hearing-specific questions are asked, and follow-up questions appear only
 * when relevant. Large toggle buttons make it usable on a phone.
 */
class HearingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                static::context(),
                static::court(),
                static::attendance(),
                static::session(),
                Section::make(__('hearings.sections.notes'))
                    ->schema([
                        Textarea::make('notes')->label(__('hearings.fields.notes'))->rows(4)->helperText(__('hearings.help.notes')),
                    ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(?CourtCase $case, ?User $user): array
    {
        return [
            'case_id' => $case?->id,
            'court_id' => $case?->court_id,
            'judge_id' => $case?->judge_id,
            'prosecutor_id' => $case?->prosecutor_id,
            'hearing_date' => Carbon::now((string) config('courtwatch.display_timezone'))->toDateString(),
            'monitor_id' => $user?->monitor?->id,
            'presentParties' => $case?->parties()->whereIn('role', [PartyRole::Accused->value, PartyRole::LegalEntity->value])->pluck('id')->map(fn ($id): string => (string) $id)->all() ?? [],
            'venue' => Venue::Courtroom->value,
            'is_public' => true,
            'started_late' => false,
            'was_postponed' => false,
            'phone_used' => false,
            'interpretation_needed' => false,
            'security_adequate' => true,
            'recording' => null,
            'participants' => [],
        ];
    }

    protected static function context(): Section
    {
        return Section::make(__('hearings.sections.context'))
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                Select::make('case_id')
                    ->label(__('hearings.fields.case'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $user = auth()->user();

                        return CourtCase::query()
                            ->when($user instanceof User, fn (Builder $query) => $query->visibleTo($user))
                            ->where(fn (Builder $query) => $query
                                ->where('court_case_number', 'ilike', "%{$search}%")
                                ->orWhere('indictment_number', 'ilike', "%{$search}%"))
                            ->with('court')
                            ->limit(25)
                            ->get()
                            ->mapWithKeys(fn (CourtCase $case): array => [$case->id => $case->display_label])
                            ->all();
                    })
                    ->getOptionLabelUsing(fn ($value): ?string => CourtCase::query()->with('court')->find($value)?->display_label)
                    ->required()
                    ->live()
                    ->disabled(fn (string $operation, Get $get): bool => $operation === 'edit')
                    ->dehydrated()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $case = $state ? CourtCase::query()->find($state) : null;

                        if ($case === null) {
                            return;
                        }

                        $set('court_id', $case->court_id);
                        $set('judge_id', $case->judge_id);
                        $set('prosecutor_id', $case->prosecutor_id);
                        $set('presentParties', $case->parties()->whereIn('role', [PartyRole::Accused->value, PartyRole::LegalEntity->value])->pluck('id')->map(fn ($id): string => (string) $id)->all());
                    })
                    ->columnSpanFull(),
                DatePicker::make('hearing_date')
                    ->label(__('hearings.fields.hearing_date'))
                    ->required()
                    ->rules([new NotInFuture])
                    ->maxDate(fn (): string => Carbon::now((string) config('courtwatch.display_timezone'))->toDateString()),
                Select::make('hearing_type')
                    ->label(__('hearings.fields.hearing_type'))
                    ->options(HearingType::options()),
                Select::make('monitor_id')
                    ->label(__('hearings.fields.monitor'))
                    ->options(fn (): array => Monitor::selectOptions())
                    ->searchable()
                    ->disabled(fn (): bool => auth()->user() instanceof User && auth()->user()->isMonitorOnly())
                    ->dehydrated(),
            ]);
    }

    protected static function court(): Section
    {
        return Section::make(__('hearings.sections.court'))
            ->description(__('hearings.help.prefilled'))
            ->collapsible()
            ->collapsed(fn (string $operation, Get $get): bool => $operation === 'create' && filled($get('court_id')))
            ->columns(['default' => 1, 'md' => 3])
            ->schema([
                RefSelect::make('court_id', Court::class)->label(__('hearings.fields.court'))->live(),
                RefSelect::make('judge_id', Judge::class, fn (Builder $query, Get $get) => $query->when($get('court_id'), fn (Builder $query, $id) => $query->where('court_id', $id)))
                    ->label(__('hearings.fields.judge')),
                RefSelect::make('prosecutor_id', Prosecutor::class)->label(__('hearings.fields.prosecutor')),
            ]);
    }

    protected static function attendance(): Section
    {
        return Section::make(__('hearings.sections.attendance'))
            ->schema([
                CheckboxList::make('presentParties')
                    ->label(__('hearings.fields.present_parties'))
                    ->relationship(
                        'presentParties',
                        'role',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('case_parties.case_id', $get('case_id') ?? 0),
                    )
                    ->getOptionLabelFromRecordUsing(fn (CaseParty $record): string => $record->display_label)
                    ->columns(['default' => 1, 'sm' => 2])
                    ->visible(fn (Get $get): bool => filled($get('case_id'))),
                ToggleButtons::make('participants')
                    ->label(__('hearings.fields.participants'))
                    ->options(Participant::options())
                    ->multiple()
                    ->inline(),
            ]);
    }

    protected static function session(): Section
    {
        $yes = fn (Get $get, string $field): bool => in_array($get($field), [true, 1, '1', 'true'], true);

        return Section::make(__('hearings.sections.session'))
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                ToggleButtons::make('venue')->label(__('hearings.fields.venue'))->options(Venue::options())->inline(),
                ToggleButtons::make('is_public')
                    ->label(__('hearings.fields.is_public'))
                    ->boolean(__('hearings.answers.public'), __('hearings.answers.closed'))
                    ->inline()
                    ->grouped(),

                ToggleButtons::make('started_late')
                    ->label(__('hearings.fields.started_late'))
                    ->helperText(__('hearings.help.started_late'))
                    ->boolean(__('hearings.answers.late'), __('hearings.answers.on_time'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                ToggleButtons::make('delay_cause')
                    ->label(__('hearings.fields.delay_cause'))
                    ->options(DelayCause::options())
                    ->inline()
                    ->visible(fn (Get $get): bool => $yes($get, 'started_late')),
                TextInput::make('delay_minutes')
                    ->label(__('hearings.fields.delay_minutes'))
                    ->integer()
                    ->minValue(16)
                    ->maxValue(600)
                    ->suffix('min')
                    ->visible(fn (Get $get): bool => $yes($get, 'started_late')),

                ToggleButtons::make('was_postponed')
                    ->label(__('hearings.fields.was_postponed'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                ToggleButtons::make('postponement_reasons')
                    ->label(__('hearings.fields.postponement_reasons'))
                    ->options(PostponementReason::options())
                    ->multiple()
                    ->inline()
                    ->required(fn (Get $get): bool => $yes($get, 'was_postponed'))
                    ->visible(fn (Get $get): bool => $yes($get, 'was_postponed'))
                    ->columnSpanFull(),

                ToggleButtons::make('recording')->label(__('hearings.fields.recording'))->options(Recording::options())->inline()->columnSpanFull(),

                ToggleButtons::make('interpretation_needed')
                    ->label(__('hearings.fields.interpretation_needed'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                ToggleButtons::make('interpretation_provided')
                    ->label(__('hearings.fields.interpretation_provided'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped()
                    ->visible(fn (Get $get): bool => $yes($get, 'interpretation_needed')),

                ToggleButtons::make('phone_used')
                    ->label(__('hearings.fields.phone_used'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped()
                    ->live()
                    ->required(),
                ToggleButtons::make('phone_users')
                    ->label(__('hearings.fields.phone_users'))
                    ->options(PhoneUser::options())
                    ->multiple()
                    ->inline()
                    ->visible(fn (Get $get): bool => $yes($get, 'phone_used'))
                    ->columnSpanFull(),

                ToggleButtons::make('security_adequate')
                    ->label(__('hearings.fields.security_adequate'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped(),
                Textarea::make('security_comment')
                    ->label(__('hearings.fields.security_comment'))
                    ->rows(2),
            ]);
    }
}
