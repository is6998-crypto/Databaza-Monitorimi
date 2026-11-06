<?php

namespace App\Filament\Admin\Resources\Reference\DefendantPositions\Pages;

use App\Filament\Admin\Resources\Reference\DefendantPositions\DefendantPositionResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageDefendantPositions extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = DefendantPositionResource::class;
}
