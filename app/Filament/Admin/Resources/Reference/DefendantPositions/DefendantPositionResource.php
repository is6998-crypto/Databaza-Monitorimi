<?php

namespace App\Filament\Admin\Resources\Reference\DefendantPositions;

use App\Filament\Admin\Resources\Reference\DefendantPositions\Pages\ManageDefendantPositions;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\DefendantPosition;
use Filament\Support\Icons\Heroicon;

class DefendantPositionResource extends ReferenceResource
{
    protected static ?string $model = DefendantPosition::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'reference/defendant-positions';

    public static function key(): string
    {
        return 'defendant_positions';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDefendantPositions::route('/'),
        ];
    }
}
