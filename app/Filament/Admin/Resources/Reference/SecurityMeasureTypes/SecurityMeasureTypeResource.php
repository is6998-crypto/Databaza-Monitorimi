<?php

namespace App\Filament\Admin\Resources\Reference\SecurityMeasureTypes;

use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Filament\Admin\Resources\Reference\SecurityMeasureTypes\Pages\ManageSecurityMeasureTypes;
use App\Models\Reference\SecurityMeasureType;
use Filament\Support\Icons\Heroicon;

class SecurityMeasureTypeResource extends ReferenceResource
{
    protected static ?string $model = SecurityMeasureType::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'reference/security-measure-types';

    public static function key(): string
    {
        return 'security_measure_types';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSecurityMeasureTypes::route('/'),
        ];
    }
}
