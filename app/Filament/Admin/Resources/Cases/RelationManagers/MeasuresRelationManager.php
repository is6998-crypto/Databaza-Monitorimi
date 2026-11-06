<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\MeasureKind;
use App\Enums\PartyRole;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Measure;
use App\Models\Reference\SecurityMeasureType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Security measures, seizures and confiscations — requested and granted, with descriptions.
 */
class MeasuresRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'measures';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.measures.title');
    }

    public static function kind(mixed $state): ?MeasureKind
    {
        return $state instanceof MeasureKind ? $state : MeasureKind::tryFrom((string) $state);
    }

    public function form(Schema $schema): Schema
    {
        /** @var CourtCase $case */
        $case = $this->getOwnerRecord();

        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Select::make('kind')->label(__('cases.measures.kind'))->options(MeasureKind::options())->required()->live(),
                Select::make('case_party_id')
                    ->label(__('cases.measures.party'))
                    ->options(fn (): array => $case->parties()
                        ->whereIn('role', [PartyRole::Accused->value, PartyRole::LegalEntity->value])
                        ->get()
                        ->mapWithKeys(fn (CaseParty $party): array => [$party->id => $party->display_label])
                        ->all()),
                RefSelect::make('security_measure_type_id', SecurityMeasureType::class)
                    ->label(__('cases.measures.type'))
                    ->visible(fn (Get $get): bool => static::kind($get('kind')) === MeasureKind::SecurityMeasure)
                    ->required(fn (Get $get): bool => static::kind($get('kind')) === MeasureKind::SecurityMeasure),
                ToggleButtons::make('requested')->label(__('cases.measures.requested'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped()->default(true),
                DatePicker::make('requested_on')->label(__('cases.measures.requested_on')),
                ToggleButtons::make('granted')->label(__('cases.measures.granted'))->boolean(__('app.yes'), __('app.no'))->inline()->grouped()->helperText(__('cases.measures.granted_help')),
                DatePicker::make('decided_on')->label(__('cases.measures.decided_on'))->afterOrEqual('requested_on'),
                TextInput::make('amount_eur')
                    ->label(__('cases.measures.amount_eur'))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€')
                    ->visible(fn (Get $get): bool => in_array(static::kind($get('kind')), [MeasureKind::Seizure, MeasureKind::Confiscation], true)),
                Textarea::make('description')->label(__('cases.measures.description'))->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['party', 'securityMeasureType']))
            ->columns([
                TextColumn::make('kind')->label(__('cases.measures.kind'))->badge(),
                TextColumn::make('security_measure_type_id')->label(__('cases.measures.type'))
                    ->formatStateUsing(fn (Measure $record): string => $record->securityMeasureType?->label() ?? '—'),
                TextColumn::make('case_party_id')->label(__('cases.measures.party'))
                    ->formatStateUsing(fn (Measure $record): string => $record->party->display_label ?? '—')->toggleable(),
                IconColumn::make('requested')->label(__('cases.measures.requested'))->boolean(),
                IconColumn::make('granted')->label(__('cases.measures.granted'))->boolean(),
                TextColumn::make('amount_eur')->label(__('cases.measures.amount_eur'))->money('EUR')->placeholder('—'),
                TextColumn::make('description')->label(__('cases.measures.description'))->limit(40)->toggleable(),
            ])
            ->headerActions([CreateAction::make()->label(__('cases.measures.add'))])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading(__('cases.measures.empty'));
    }
}
