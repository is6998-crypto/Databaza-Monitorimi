<?php

namespace App\Filament\Research\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Personal API tokens (Sanctum, ability "research:read") for the researcher API.
 */
class ApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.research.api-tokens';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'api-tokens';

    public ?string $plainTextToken = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isApprovedResearcher();
    }

    public function getTitle(): string
    {
        return __('research.tokens.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('research.tokens.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label(__('research.tokens.create'))
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('name')->label(__('research.tokens.name'))->required()->maxLength(100),
                    Select::make('expires_in_days')->label(__('research.tokens.expires'))->options([30 => '30', 90 => '90', 180 => '180', 365 => '365'])->default(90)->required(),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    abort_unless($user instanceof User && static::canAccess(), 403);

                    $token = $user->createToken($data['name'], ['research:read'], now()->addDays((int) $data['expires_in_days']));
                    $this->plainTextToken = $token->plainTextToken;

                    activity('researchers')->causedBy($user)->performedOn($token->accessToken)->event('token_created')->log('API token created');

                    Notification::make()->title(__('research.tokens.created'))->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => PersonalAccessToken::query()->where('tokenable_type', (new User)->getMorphClass())->where('tokenable_id', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('research.tokens.name')),
                TextColumn::make('created_at')->label(__('research.tokens.created_at'))->dateTime(),
                TextColumn::make('last_used_at')->label(__('research.tokens.last_used'))->dateTime()->placeholder('—'),
                TextColumn::make('expires_at')->label(__('research.tokens.expires_at'))->dateTime()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(__('research.tokens.revoke'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (PersonalAccessToken $record): void {
                        abort_unless($record->tokenable_id === auth()->id(), 403);
                        $record->delete();
                        activity('researchers')->causedBy(auth()->user())->event('token_revoked')->log('API token revoked');
                    }),
            ])
            ->emptyStateHeading(__('research.tokens.empty'));
    }
}
