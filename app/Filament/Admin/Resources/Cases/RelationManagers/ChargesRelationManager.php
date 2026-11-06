<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\ChargeStage;
use App\Enums\PartyRole;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Reference\CriminalOffence;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Charges: party × offence × stage (criminal report, indictment, judgment).
 */
class ChargesRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'charges';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.charges.title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof CourtCase
            && $ownerRecord->type->isCriminal()
            && Gate::allows('view', $ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        /** @var CourtCase $case */
        $case = $this->getOwnerRecord();

        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Select::make('case_party_id')
                    ->label(__('cases.charges.party'))
                    ->options(fn (): array => $case->parties()
                        ->whereIn('role', [PartyRole::Accused->value, PartyRole::LegalEntity->value])
                        ->get()
                        ->mapWithKeys(fn (CaseParty $party): array => [$party->id => $party->display_label])
                        ->all())
                    ->required(),
                Select::make('stage')
                    ->label(__('cases.charges.stage'))
                    ->options(ChargeStage::options())
                    ->default(ChargeStage::Indictment->value)
                    ->required(),
                RefSelect::make('criminal_offence_id', CriminalOffence::class)
                    ->label(__('cases.charges.offence'))
                    ->required()
                    ->columnSpanFull()
                    ->rules([
                        fn (Get $get, ?Charge $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $exists = Charge::query()
                                ->where('case_party_id', $get('case_party_id'))
                                ->where('criminal_offence_id', $value)
                                ->where('stage', $get('stage'))
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail(__('cases.charges.duplicate'));
                            }
                        },
                    ]),
                Textarea::make('notes')->label(__('cases.fields.notes'))->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['party', 'offence.criminalCode']))
            ->columns([
                TextColumn::make('case_party_id')->label(__('cases.charges.party'))
                    ->formatStateUsing(fn (Charge $record): string => $record->party->display_label),
                TextColumn::make('criminal_offence_id')->label(__('cases.charges.offence'))
                    ->formatStateUsing(fn (Charge $record): string => $record->offence->label())->wrap(),
                TextColumn::make('stage')->label(__('cases.charges.stage'))->badge(),
            ])
            ->filters([
                SelectFilter::make('stage')->label(__('cases.charges.stage'))->options(ChargeStage::options()),
            ])
            ->headerActions([
                CreateAction::make()->label(__('cases.charges.add')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('cases.charges.empty'));
    }
}
