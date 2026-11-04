<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Filament\Admin\Resources\Activities\ActivityResource;
use App\Models\AdministrativeCaseDetail;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\Charge;
use App\Models\CivilCaseDetail;
use App\Models\CourtCase;
use App\Models\Measure;
use App\Models\Sentence;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

/**
 * Change history of the case and of everything inside it (parties, charges, stages…).
 */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.tabs.changes');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        /** @var CourtCase $case */
        $case = $this->getOwnerRecord();

        $children = [
            CaseParty::class => $case->parties()->withTrashed()->pluck('id'),
            Charge::class => $case->charges()->pluck('id'),
            CaseStage::class => $case->stages()->pluck('id'),
            Measure::class => $case->measures()->pluck('id'),
            Sentence::class => $case->sentences()->pluck('id'),
            AdministrativeCaseDetail::class => AdministrativeCaseDetail::query()->where('case_id', $case->id)->pluck('id'),
            CivilCaseDetail::class => CivilCaseDetail::query()->where('case_id', $case->id)->pluck('id'),
        ];

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($children): void {
                foreach ($children as $class => $ids) {
                    if ($ids->isNotEmpty()) {
                        $query->orWhere(fn (Builder $query) => $query->where('subject_type', (new $class)->getMorphClass())->whereIn('subject_id', $ids));
                    }
                }

                $query->with('causer');
            })
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('audit.fields.created_at'))->dateTime(),
                TextColumn::make('event')->label(__('audit.fields.event'))->badge()->color('gray')->formatStateUsing(fn (?string $state): string => ActivityResource::eventLabel($state)),
                TextColumn::make('subject_type')->label(__('audit.fields.subject'))->formatStateUsing(fn (?string $state, Activity $record): string => $state ? class_basename($state).' #'.$record->subject_id : '—'),
                TextColumn::make('changes')
                    ->label(__('audit.fields.changes'))
                    ->state(fn (Activity $record): string => collect(array_keys((array) ($record->properties['attributes'] ?? [])))
                        ->merge(isset($record->properties['name_changed']) ? ['name'] : [])
                        ->implode(', '))
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('causer.name')->label(__('audit.fields.causer'))->placeholder(__('audit.system')),
            ])
            ->paginated([10, 25, 50]);
    }
}
