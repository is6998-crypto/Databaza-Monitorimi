<?php

namespace App\Filament\Admin\Resources\Cases\RelationManagers;

use App\Enums\Instance;
use App\Enums\PartyRole;
use App\Enums\SentenceKind;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Sentence;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Requested and imposed sentences per party (values must be ≥ 0).
 */
class SentencesRelationManager extends RelationManager
{
    use AuthorizesThroughCase;

    protected static string $relationship = 'sentences';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cases.sentences.title');
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
            ->columns(['default' => 1, 'md' => 3])
            ->components([
                Select::make('case_party_id')
                    ->label(__('cases.sentences.party'))
                    ->options(fn (): array => $case->parties()
                        ->whereIn('role', [PartyRole::Accused->value, PartyRole::LegalEntity->value])
                        ->get()
                        ->mapWithKeys(fn (CaseParty $party): array => [$party->id => $party->display_label])
                        ->all())
                    ->required(),
                Select::make('kind')->label(__('cases.sentences.kind'))->options(SentenceKind::options())->required(),
                Select::make('instance')
                    ->label(__('cases.sentences.instance'))
                    ->options(Instance::options())
                    ->default(Instance::FirstInstance->value)
                    ->required()
                    ->rules([
                        fn (Get $get, ?Sentence $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $exists = Sentence::query()
                                ->where('case_party_id', $get('case_party_id'))
                                ->where('kind', $get('kind'))
                                ->where('instance', $value)
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($exists) {
                                $fail(__('cases.sentences.duplicate'));
                            }
                        },
                    ]),
                TextInput::make('prison_months')->label(__('cases.sentences.prison_months'))->integer()->minValue(0)->maxValue(600)->suffix(__('cases.sentences.months')),
                TextInput::make('suspended_months')->label(__('cases.sentences.suspended_months'))->integer()->minValue(0)->maxValue(600)->suffix(__('cases.sentences.months')),
                TextInput::make('fine_eur')->label(__('cases.sentences.fine_eur'))->numeric()->minValue(0)->prefix('€'),
                Textarea::make('supplementary_punishment')->label(__('cases.sentences.supplementary'))->rows(2)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('party'))
            ->columns([
                TextColumn::make('case_party_id')->label(__('cases.sentences.party'))
                    ->formatStateUsing(fn (Sentence $record): string => $record->party->display_label),
                TextColumn::make('kind')->label(__('cases.sentences.kind'))->badge(),
                TextColumn::make('instance')->label(__('cases.sentences.instance'))->toggleable(),
                TextColumn::make('prison_months')->label(__('cases.sentences.prison_months'))->numeric()->placeholder('—'),
                TextColumn::make('suspended_months')->label(__('cases.sentences.suspended_months'))->numeric()->placeholder('—'),
                TextColumn::make('fine_eur')->label(__('cases.sentences.fine_eur'))->money('EUR')->placeholder('—'),
                TextColumn::make('supplementary_punishment')->label(__('cases.sentences.supplementary'))->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([CreateAction::make()->label(__('cases.sentences.add'))])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading(__('cases.sentences.empty'));
    }
}
