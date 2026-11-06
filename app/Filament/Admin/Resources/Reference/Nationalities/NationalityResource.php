<?php

namespace App\Filament\Admin\Resources\Reference\Nationalities;

use App\Filament\Admin\Resources\Reference\Nationalities\Pages\ManageNationalities;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Nationality;
use Filament\Support\Icons\Heroicon;

class NationalityResource extends ReferenceResource
{
    protected static ?string $model = Nationality::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?int $navigationSort = 13;

    protected static ?string $slug = 'reference/nationalities';

    public static function key(): string
    {
        return 'nationalities';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNationalities::route('/'),
        ];
    }
}
