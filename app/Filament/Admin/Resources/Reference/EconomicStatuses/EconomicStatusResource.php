<?php

namespace App\Filament\Admin\Resources\Reference\EconomicStatuses;

use App\Filament\Admin\Resources\Reference\EconomicStatuses\Pages\ManageEconomicStatuses;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\EconomicStatus;
use Filament\Support\Icons\Heroicon;

class EconomicStatusResource extends ReferenceResource
{
    protected static ?string $model = EconomicStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'reference/economic-statuses';

    public static function key(): string
    {
        return 'economic_statuses';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEconomicStatuses::route('/'),
        ];
    }
}
