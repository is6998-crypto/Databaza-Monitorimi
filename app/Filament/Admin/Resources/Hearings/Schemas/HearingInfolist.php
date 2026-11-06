<?php

namespace App\Filament\Admin\Resources\Hearings\Schemas;

use App\Enums\DelayCause;
use App\Enums\Participant;
use App\Enums\PhoneUser;
use App\Enums\PostponementReason;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Models\CaseParty;
use App\Models\Hearing;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\HasLabel;

class HearingInfolist
{
    /**
     * @param  class-string<\BackedEnum&HasLabel>  $enum
     * @param  list<string>|null  $values
     */
    public static function labels(string $enum, ?array $values): ?string
    {
        if (blank($values)) {
            return null;
        }

        return collect($values)->map(fn (string $value): string => (string) ($enum::tryFrom($value)?->getLabel() ?? $value))->implode(', ');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Callout::make(__('cases.returned.heading'))
                    ->description(fn (Hearing $record): ?string => $record->return_comment)
                    ->danger()
                    ->visible(fn (Hearing $record): bool => $record->status === WorkflowStatus::Returned && filled($record->return_comment)),

                Section::make(__('hearings.sections.context'))
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('case.display_label')
                            ->label(__('hearings.fields.case'))
                            ->url(fn (Hearing $record): string => CourtCaseResource::getUrl('view', ['record' => $record->case_id]))
                            ->color('primary'),
                        TextEntry::make('hearing_date')->label(__('hearings.fields.hearing_date'))->date(),
                        TextEntry::make('hearing_type')->label(__('hearings.fields.hearing_type'))->badge()->placeholder('—'),
                        TextEntry::make('status')->label(__('cases.fields.status'))->badge(),
                        TextEntry::make('court')->label(__('hearings.fields.court'))->state(fn (Hearing $record): ?string => $record->court?->label())->placeholder('—'),
                        TextEntry::make('judge.name')->label(__('hearings.fields.judge'))->placeholder('—'),
                        TextEntry::make('prosecutor.name')->label(__('hearings.fields.prosecutor'))->placeholder('—'),
                        TextEntry::make('monitor.user.name')->label(__('hearings.fields.monitor'))->placeholder('—'),
                    ]),

                Section::make(__('hearings.sections.attendance'))
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('present')->label(__('hearings.fields.present_parties'))
                            ->state(fn (Hearing $record): ?string => $record->presentParties->map(fn (CaseParty $party): string => $party->display_label)->implode(', ') ?: null)
                            ->placeholder('—'),
                        TextEntry::make('participants')->label(__('hearings.fields.participants'))
                            ->state(fn (Hearing $record): ?string => static::labels(Participant::class, $record->participants))
                            ->placeholder('—'),
                    ]),

                Section::make(__('hearings.sections.session'))
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->schema([
                        TextEntry::make('venue')->label(__('hearings.fields.venue'))->placeholder('—'),
                        IconEntry::make('is_public')->label(__('hearings.fields.is_public'))->boolean(),
                        IconEntry::make('started_late')->label(__('hearings.fields.started_late'))->boolean()->trueColor('warning')->falseColor('success'),
                        TextEntry::make('delay')->label(__('hearings.fields.delay_cause'))
                            ->state(fn (Hearing $record): ?string => $record->started_late ? trim(($record->delay_cause instanceof DelayCause ? $record->delay_cause->getLabel() : '').($record->delay_minutes ? ' · '.$record->delay_minutes.' min' : '')) : null)
                            ->placeholder('—'),
                        IconEntry::make('was_postponed')->label(__('hearings.fields.was_postponed'))->boolean()->trueColor('warning')->falseColor('success'),
                        TextEntry::make('postponement_reasons')->label(__('hearings.fields.postponement_reasons'))
                            ->state(fn (Hearing $record): ?string => static::labels(PostponementReason::class, $record->postponement_reasons))
                            ->placeholder('—'),
                        TextEntry::make('recording')->label(__('hearings.fields.recording'))->placeholder('—'),
                        IconEntry::make('interpretation_needed')->label(__('hearings.fields.interpretation_needed'))->boolean(),
                        IconEntry::make('interpretation_provided')->label(__('hearings.fields.interpretation_provided'))->boolean()->visible(fn (Hearing $record): bool => $record->interpretation_needed),
                        IconEntry::make('phone_used')->label(__('hearings.fields.phone_used'))->boolean()->trueColor('warning')->falseColor('success'),
                        TextEntry::make('phone_users')->label(__('hearings.fields.phone_users'))
                            ->state(fn (Hearing $record): ?string => static::labels(PhoneUser::class, $record->phone_users))
                            ->placeholder('—'),
                        IconEntry::make('security_adequate')->label(__('hearings.fields.security_adequate'))->boolean(),
                        TextEntry::make('security_comment')->label(__('hearings.fields.security_comment'))->placeholder('—')->columnSpanFull(),
                        TextEntry::make('notes')->label(__('hearings.fields.notes'))->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
