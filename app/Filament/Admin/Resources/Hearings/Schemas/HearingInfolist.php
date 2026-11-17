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
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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

    /**
     * A yes/no answer as a word in a chip. An answer that is a finding (late, postponed, closed) is yellow.
     */
    protected static function answer(string $field, string $label, ?string $yes = null, ?string $no = null, string $yesColor = 'gray', string $noColor = 'gray'): TextEntry
    {
        return TextEntry::make($field)
            ->label($label)
            ->state(fn (Hearing $record): ?string => match ($record->getAttribute($field)) {
                true => $yes ?? (string) __('app.yes'),
                false => $no ?? (string) __('app.no'),
                default => null,
            })
            ->badge()
            ->color(fn (Hearing $record): string => $record->getAttribute($field) ? $yesColor : $noColor)
            ->placeholder('—');
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

                // What happened, at a glance.
                View::make('filament.admin.hearings.facts'),

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
                        // Answers in words: a tick or a cross does not answer "public or closed?".
                        static::answer('is_public', __('hearings.fields.is_public'), __('hearings.answers.public'), __('hearings.answers.closed'), noColor: 'warning'),
                        static::answer('started_late', __('hearings.fields.started_late'), __('hearings.answers.late'), __('hearings.answers.on_time'), yesColor: 'warning'),
                        TextEntry::make('delay')->label(__('hearings.fields.delay_causes'))
                            ->state(fn (Hearing $record): ?string => $record->started_late ? trim((static::labels(DelayCause::class, $record->delay_causes) ?? '').($record->delay_minutes ? ' · '.$record->delay_minutes.' min' : ''), ' ·') : null)
                            ->placeholder('—'),
                        static::answer('was_postponed', __('hearings.fields.was_postponed'), yesColor: 'warning'),
                        TextEntry::make('postponement_reasons')->label(__('hearings.fields.postponement_reasons'))
                            ->state(fn (Hearing $record): ?string => static::labels(PostponementReason::class, $record->postponement_reasons))
                            ->placeholder('—'),
                        static::answer('plea_agreement', __('hearings.fields.plea_agreement')),
                        TextEntry::make('recording')->label(__('hearings.fields.recording'))->placeholder('—'),
                        static::answer('interpretation_needed', __('hearings.fields.interpretation_needed')),
                        static::answer('interpretation_provided', __('hearings.fields.interpretation_provided'), noColor: 'warning')
                            ->visible(fn (Hearing $record): bool => $record->interpretation_needed),
                        static::answer('phone_used', __('hearings.fields.phone_used'), yesColor: 'warning'),
                        TextEntry::make('phone_users')->label(__('hearings.fields.phone_users'))
                            ->state(fn (Hearing $record): ?string => static::labels(PhoneUser::class, $record->phone_users))
                            ->placeholder('—'),
                        static::answer('security_adequate', __('hearings.fields.security_adequate'), noColor: 'warning'),
                        TextEntry::make('security_comment')->label(__('hearings.fields.security_comment'))->placeholder('—')->columnSpanFull(),
                        TextEntry::make('notes')->label(__('hearings.fields.notes'))->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
