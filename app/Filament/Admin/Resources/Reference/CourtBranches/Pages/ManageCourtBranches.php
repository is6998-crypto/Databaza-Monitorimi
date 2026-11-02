<?php

namespace App\Filament\Admin\Resources\Reference\CourtBranches\Pages;

use App\Filament\Admin\Resources\Reference\CourtBranches\CourtBranchResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageCourtBranches extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = CourtBranchResource::class;
}
