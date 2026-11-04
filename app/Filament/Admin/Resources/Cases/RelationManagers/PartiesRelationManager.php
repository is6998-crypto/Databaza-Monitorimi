<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\CounselType;
use App\Enums\Gender;
use App\Enums\PartyRole;
use App\Filament\Admin\Support\RefSelect;
use App\Models\CaseParty;
use App\Models\Reference\DefendantPosition;
use App\Models\Reference\EconomicStatus;
use App\Models\Reference\Nationality;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Parties of the case. Names are encrypted at rest and never leave the staff panel.
 */
class PartiesRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'parties';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.parties.title');
    }

    public static function role(mixed $state): ?PartyRole
    {
        return $state instanceof PartyRole ? $state : PartyRole::tryFrom((string) $state);
    }

    public static function isDefendant(Get $get): bool
    {
        return in_array(static::role($get('role')), [PartyRole::Accused, PartyRole::LegalEntity], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 2])
            ->components([
                Select::make('role')
                    ->label(__('cases.parties.role'))
                    ->options(PartyRole::options())
                    ->default(PartyRole::Accused->value)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state === PartyRole::LegalEntity->value) {
                            $set('gender', Gender::LegalPerson->value);
                        }
                    }),
                TextInput::make('name')
                    ->label(__('cases.parties.name'))
                    ->helperText(__('cases.parties.name_help'))
                    ->required()
                    ->maxLength(255),
                Select::make('gender')
                    ->label(__('cases.parties.gender'))
                    ->options(Gender::options()),
                RefSelect::make('nationality_id', Nationality::class)
                    ->label(__('cases.parties.nationality')),
                RefSelect::make('economic_status_id', EconomicStatus::class)
                    ->label(__('cases.parties.economic_status'))
                    ->visible(fn (Get $get): bool => static::role($get('role')) !== PartyRole::LegalEntity),
                RefSelect::make('defendant_position_id', DefendantPosition::class)
                    ->label(__('cases.parties.position'))
                    ->visible(fn (Get $get): bool => static::role($get('role')) === PartyRole::Accused),
                Select::make('counsel_type')
                    ->label(__('cases.parties.counsel_type'))
                    ->options(CounselType::options())
                    ->visible(fn (Get $get): bool => static::isDefendant($get)),
                ToggleButtons::make('counsel_at_police')
                    ->label(__('cases.parties.counsel_at_police'))
                    ->boolean(__('app.yes'), __('app.no'))
                    ->inline()
                    ->grouped()
                    ->visible(fn (Get $get): bool => static::role($get('role')) === PartyRole::Accused),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['nationality', 'defendantPosition'])->withCount('charges'))
            ->recordTitle(fn (CaseParty $record): string => $record->display_label)
            ->columns([
                TextColumn::make('role')->label(__('cases.parties.role'))->badge(),
                TextColumn::make('name')->label(__('cases.parties.name'))->weight('medium'),
                TextColumn::make('gender')->label(__('cases.parties.gender'))->toggleable(),
                TextColumn::make('nationality_id')->label(__('cases.parties.nationality'))
                    ->formatStateUsing(fn (CaseParty $record): string => $record->nationality?->label() ?? '—')->toggleable(),
                TextColumn::make('defendant_position_id')->label(__('cases.parties.position'))
                    ->formatStateUsing(fn (CaseParty $record): string => $record->defendantPosition?->label() ?? '—')->toggleable(),
                TextColumn::make('counsel_type')->label(__('cases.parties.counsel_type'))->toggleable(),
                IconColumn::make('counsel_at_police')->label(__('cases.parties.counsel_at_police_short'))->boolean()->toggleable(),
                TextColumn::make('charges_count')->label(__('cases.charges.title'))->numeric()->alignEnd(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('cases.parties.add')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('cases.parties.empty'));
    }
}
