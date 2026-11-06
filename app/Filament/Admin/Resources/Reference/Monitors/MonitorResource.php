<?php

namespace App\Filament\Admin\Resources\Reference\Monitors;

use App\Enums\Role;
use App\Filament\Admin\Resources\Reference\Monitors\Pages\ManageMonitors;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Monitor;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class MonitorResource extends ReferenceResource
{
    protected static ?string $model = Monitor::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'reference/monitors';

    public static function key(): string
    {
        return 'monitors';
    }

    protected static function extraFormFields(): array
    {
        return [
            Select::make('user_id')
                ->label(__('reference.fields.user'))
                ->relationship('user', 'name', fn (EloquentBuilder $query) => $query->whereHas('roles', fn (EloquentBuilder $roles) => $roles->where('name', Role::Monitor->value)))
                ->searchable()
                ->preload()
                ->unique(ignoreRecord: true)
                ->required(),
            TextInput::make('code')->label(__('reference.fields.code'))->maxLength(32),
            TextInput::make('phone')->label(__('reference.fields.phone'))->tel()->maxLength(32),
            Select::make('courts')->label(__('reference.fields.courts'))->relationship('courts', 'name_sq')->multiple()->searchable()->preload()->columnSpanFull(),
        ];
    }

    protected static function extraColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('reference.fields.code'))->searchable()->sortable(),
            TextColumn::make('courts.name_sq')->label(__('reference.fields.courts'))->badge()->toggleable(),
        ];
    }

    /**
     * @return array<string, array{rules?: list<mixed>|string, resolve?: \Closure(string): (int|null), attribute?: string}>
     */
    protected static function extraCsvColumns(): array
    {
        return [
            'email' => ['rules' => ['required', 'email'], 'resolve' => fn (string $email): ?int => User::query()->where('email', mb_strtolower(trim($email)))->value('id'), 'attribute' => 'user_id'],
            'code' => ['rules' => ['nullable', 'string', 'max:32']],
            'phone' => ['rules' => ['nullable', 'string', 'max:32']],
        ];
    }

    public static function csvMatchOn(): array
    {
        return ['user_id'];
    }

    protected static function nameFields(): array
    {
        return [];
    }

    protected static function labelColumns(): array
    {
        return [
            TextColumn::make('user.name')->label(__('reference.fields.user'))->searchable()->sortable(),
        ];
    }

    public static function csvColumns(): array
    {
        return static::extraCsvColumns();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMonitors::route('/'),
        ];
    }
}
