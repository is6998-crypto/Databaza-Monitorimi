<?php

namespace App\Filament\Admin\Resources\Cases\Schemas;

use App\Enums\CaseType;
use App\Enums\WorkflowStatus;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * "Overview" tab of the case workspace.
 */
class CourtCaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Callout::make(__('cases.returned.heading'))
                    ->description(fn (CourtCase $record): ?string => $record->return_comment)
                    ->danger()
                    ->visible(fn (CourtCase $record): bool => $record->status === WorkflowStatus::Returned && filled($record->return_comment)),

                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    Section::make(__('cases.sections.case'))
                        ->columnSpan(['default' => 1, 'lg' => 2])
                        ->columns(['default' => 1, 'sm' => 2])
                        ->schema([
                            TextEntry::make('court_case_number')->label(__('cases.fields.court_case_number'))->placeholder('—')->copyable(),
                            TextEntry::make('type')->label(__('cases.fields.type'))->badge(),
                            TextEntry::make('indictment_number')->label(__('cases.fields.indictment_number'))->placeholder('—')
                                ->visible(fn (CourtCase $record): bool => $record->type->isCriminal()),
                            TextEntry::make('police_case_number')->label(__('cases.fields.police_case_number'))->placeholder('—')
                                ->visible(fn (CourtCase $record): bool => $record->type->isCriminal()),
                            TextEntry::make('court')->label(__('cases.fields.court'))->state(fn (CourtCase $record): ?string => $record->court?->label()),
                            TextEntry::make('courtBranch')->label(__('cases.fields.court_branch'))->state(fn (CourtCase $record): ?string => $record->courtBranch?->label())->placeholder('—'),
                            TextEntry::make('department')->label(__('cases.fields.department'))->state(fn (CourtCase $record): ?string => $record->department?->label())->placeholder('—'),
                            TextEntry::make('division')->label(__('cases.fields.division'))->state(fn (CourtCase $record): ?string => $record->division?->label())->placeholder('—'),
                            TextEntry::make('judge.name')->label(__('cases.fields.judge'))->placeholder('—'),
                            TextEntry::make('prosecutionOffice')->label(__('cases.fields.prosecution_office'))->state(fn (CourtCase $record): ?string => $record->prosecutionOffice?->label())->placeholder('—')
                                ->visible(fn (CourtCase $record): bool => $record->type->isCriminal()),
                            TextEntry::make('prosecutor.name')->label(__('cases.fields.prosecutor'))->placeholder('—')
                                ->visible(fn (CourtCase $record): bool => $record->type->isCriminal()),
                            TextEntry::make('summary')->label(__('cases.fields.summary'))->placeholder('—')->columnSpanFull(),
                        ]),

                    Section::make(__('cases.sections.status'))
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('status')->label(__('cases.fields.status'))->badge(),
                            TextEntry::make('public_code')->label(__('cases.fields.public_code'))->helperText(__('cases.help.public_code')),
                            TextEntry::make('assignedMonitor.user.name')->label(__('cases.fields.assigned_monitor'))->placeholder('—'),
                            TextEntry::make('creator.name')->label(__('cases.fields.created_by'))->placeholder('—'),
                            TextEntry::make('created_at')->label(__('cases.fields.created_at'))->dateTime(),
                            TextEntry::make('updated_at')->label(__('cases.fields.updated_at'))->dateTime(),
                        ]),
                ]),

                Section::make(__('cases.sections.progress'))
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        TextEntry::make('latest_stage')
                            ->label(__('cases.fields.latest_stage'))
                            ->state(function (CourtCase $record): ?string {
                                /** @var CaseStage|null $stage */
                                $stage = $record->stages->sortBy(fn (CaseStage $stage): int => $stage->stage->order())->last();

                                return $stage ? $stage->stage->getLabel().($stage->date ? ' · '.$stage->date->format('d.m.Y') : '') : null;
                            })
                            ->placeholder('—'),
                        TextEntry::make('hearings_total')->label(__('cases.fields.hearings_count'))->state(fn (CourtCase $record): int => $record->hearings->count()),
                        TextEntry::make('hearings_postponed')->label(__('cases.fields.postponed_count'))->state(fn (CourtCase $record): int => $record->hearings->where('was_postponed', true)->count()),
                        TextEntry::make('last_hearing')
                            ->label(__('cases.fields.last_hearing'))
                            ->state(fn (CourtCase $record): ?string => $record->hearings->sortByDesc(fn (Hearing $hearing) => $hearing->hearing_date)->first()?->hearing_date->format('d.m.Y'))
                            ->placeholder('—'),
                    ]),

                Section::make(__('cases.sections.administrative'))
                    ->visible(fn (CourtCase $record): bool => $record->type === CaseType::Administrative && $record->administrativeDetails !== null)
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->schema([
                        TextEntry::make('administrativeDetails.first_instance_decision')->label(__('cases.admin.first_instance_decision'))->placeholder('—')->columnSpanFull(),
                        TextEntry::make('administrativeDetails.first_instance_decision_date')->label(__('cases.admin.first_instance_decision_date'))->date()->placeholder('—'),
                        IconEntry::make('administrativeDetails.decision_reasoned')->label(__('cases.admin.decision_reasoned'))->boolean(),
                        TextEntry::make('administrativeDetails.legal_remedy_type')->label(__('cases.admin.legal_remedy_type'))->badge()->placeholder('—'),
                        TextEntry::make('administrativeDetails.second_instance_outcome')->label(__('cases.admin.second_instance_outcome'))->badge()->placeholder('—'),
                        IconEntry::make('administrativeDetails.conflict_filed')->label(__('cases.admin.conflict_filed'))->boolean(),
                        TextEntry::make('administrativeDetails.plaintiff_type')->label(__('cases.admin.plaintiff_type'))->placeholder('—'),
                        TextEntry::make('administrativeDetails.respondent_body')->label(__('cases.admin.respondent_body'))->placeholder('—'),
                        TextEntry::make('administrativeDetails.representation')->label(__('cases.admin.representation'))->placeholder('—'),
                    ]),

                Section::make(__('cases.sections.civil'))
                    ->visible(fn (CourtCase $record): bool => $record->type->usesCivilDetails() && $record->civilDetails !== null)
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                    ->schema([
                        TextEntry::make('civilDetails.claim_type')->label(__('cases.civil.claim_type'))->placeholder('—'),
                        TextEntry::make('civilDetails.claim_value_eur')->label(__('cases.civil.claim_value_eur'))->money('EUR')->placeholder('—'),
                        TextEntry::make('civilDetails.filed_date')->label(__('cases.civil.filed_date'))->date()->placeholder('—'),
                        TextEntry::make('civilDetails.plaintiff_type')->label(__('cases.civil.plaintiff_type'))->placeholder('—'),
                        TextEntry::make('civilDetails.respondent_type')->label(__('cases.civil.respondent_type'))->placeholder('—'),
                        TextEntry::make('civilDetails.outcome')->label(__('cases.civil.outcome'))->badge()->placeholder('—'),
                    ]),
            ]);
    }
}
