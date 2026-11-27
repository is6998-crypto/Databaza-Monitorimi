<?php

namespace App\Filament\Admin\Resources\ScheduledHearings\Schemas;

use App\Models\CourtCase;
use App\Models\Reference\Monitor;
use App\Models\ScheduledHearing;
use App\Models\User;
use App\Support\Permissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ScheduledHearingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('case_id')
                            ->label(__('hearings.fields.case'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => self::cases($search))
                            ->getOptionLabelUsing(fn ($value): ?string => CourtCase::query()->with('court')->find($value)?->display_label)
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->columnSpanFull(),
                        DatePicker::make('scheduled_on')
                            ->label(__('calendar.fields.scheduled_on'))
                            ->required()
                            ->minDate(fn (string $operation): ?string => $operation === 'create' ? ScheduledHearing::today()->toDateString() : null),
                        TextInput::make('scheduled_time')
                            ->label(__('calendar.fields.scheduled_time'))
                            ->type('time')
                            ->regex('/^([01]\d|2[0-3]):[0-5]\d$/')
                            ->placeholder('10:00'),
                        TextInput::make('courtroom')
                            ->label(__('calendar.fields.courtroom'))
                            ->maxLength(60),
                        // Editors choose who attends; a monitor's own entry goes to the monitor of the case, or to them.
                        Select::make('monitor_id')
                            ->label(__('calendar.fields.monitor'))
                            ->helperText(__('calendar.help.monitor'))
                            ->options(fn (): array => Monitor::selectOptions())
                            ->searchable()
                            ->placeholder(__('calendar.nobody'))
                            ->disabled(fn (?ScheduledHearing $record): bool => ! static::canAssign($record))
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label(__('calendar.fields.note'))
                            ->helperText(__('hearings.help.notes'))
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function canAssign(?ScheduledHearing $record): bool
    {
        $user = auth()->user();

        return $record === null
            ? Gate::allows('create', ScheduledHearing::class) && $user instanceof User && $user->checkPermissionTo(Permissions::ASSIGN_MONITOR)
            : Gate::allows('assign', $record);
    }

    /**
     * @return array<int, string>
     */
    private static function cases(string $search): array
    {
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
    }
}
