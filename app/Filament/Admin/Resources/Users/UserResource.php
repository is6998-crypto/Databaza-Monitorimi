<?php

namespace App\Filament\Admin\Resources\Users;

use App\Enums\NavigationGroup;
use App\Enums\Role as RoleEnum;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Reference\Court;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    // Albanian labels are sentences ("Degët e gjykatave"): no Title Case.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Administration;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('users.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('users.sections.account'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('users.fields.name'))->required()->maxLength(255),
                    TextInput::make('email')->label(__('users.fields.email'))->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->label(__('users.fields.password'))
                        ->password()
                        ->revealable()
                        ->rule(Password::defaults())
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('users.help.password_keep') : null),
                    Select::make('locale')
                        ->label(__('users.fields.locale'))
                        ->options(fn (): array => array_intersect_key((array) config('courtwatch.locale_labels'), array_flip((array) config('courtwatch.supported_locales'))))
                        ->default('sq')
                        ->required(),
                    Toggle::make('is_active')->label(__('users.fields.is_active'))->default(true),
                ]),

            Section::make(__('users.sections.roles'))
                ->schema([
                    CheckboxList::make('roles')
                        ->label(__('users.fields.roles'))
                        ->relationship('roles', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Role $record): string => RoleEnum::tryFrom($record->name)?->getLabel() ?? $record->name)
                        ->columns(['default' => 1, 'sm' => 2])
                        ->live()
                        ->disabled(fn (): bool => ! (auth()->user()?->isSuperAdmin() ?? false))
                        ->helperText(__('users.help.roles')),
                ]),

            Section::make(__('users.sections.monitor'))
                ->description(__('users.help.monitor'))
                ->columns(2)
                ->visible(fn (Get $get): bool => static::monitorRoleSelected($get('roles')))
                ->schema([
                    TextInput::make('monitor_code')->label(__('users.fields.monitor_code'))->maxLength(32),
                    TextInput::make('monitor_phone')->label(__('users.fields.monitor_phone'))->tel()->maxLength(32),
                    Select::make('monitor_court_ids')
                        ->label(__('users.fields.monitor_courts'))
                        ->multiple()
                        ->options(fn (): array => Court::selectOptions())
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * @param  mixed  $roleIds  selected role ids from the form state
     */
    public static function monitorRoleSelected(mixed $roleIds): bool
    {
        $monitorRoleId = Role::query()->where('name', RoleEnum::Monitor->value)->value('id');

        return $monitorRoleId !== null && in_array((string) $monitorRoleId, array_map('strval', (array) $roleIds), true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->label(__('users.fields.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('users.fields.email'))->searchable()->sortable()->toggleable(),
                TextColumn::make('roles.name')
                    ->label(__('users.fields.roles'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RoleEnum::tryFrom($state)?->getLabel() ?? $state),
                IconColumn::make('app_authentication_secret')
                    ->label(__('users.fields.mfa'))
                    ->state(fn (User $record): bool => $record->hasMultiFactorAuthentication())
                    ->boolean(),
                IconColumn::make('is_active')->label(__('users.fields.is_active'))->boolean()->sortable(),
                TextColumn::make('last_login_at')->label(__('users.fields.last_login_at'))->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('users.fields.roles'))
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => RoleEnum::tryFrom($record->name)?->getLabel() ?? $record->name)
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')->label(__('users.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                static::resetMfaAction(),
            ]);
    }

    public static function resetMfaAction(): Action
    {
        return Action::make('resetMfa')
            ->label(__('users.actions.reset_mfa'))
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('users.actions.reset_mfa_confirm'))
            ->visible(fn (User $record): bool => $record->hasMultiFactorAuthentication() && Gate::allows('resetMultiFactor', $record))
            ->action(function (User $record): void {
                Gate::authorize('resetMultiFactor', $record);

                $record->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();

                activity('auth')->causedBy(auth()->user())->performedOn($record)->event('mfa_reset')->log('Two-factor authentication reset by an administrator');

                Notification::make()->title(__('users.actions.reset_mfa_done'))->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
