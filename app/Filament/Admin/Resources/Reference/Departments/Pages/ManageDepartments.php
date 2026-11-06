<?php

namespace App\Filament\Admin\Resources\Reference\Departments\Pages;

use App\Filament\Admin\Resources\Reference\Departments\DepartmentResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageDepartments extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = DepartmentResource::class;
}
