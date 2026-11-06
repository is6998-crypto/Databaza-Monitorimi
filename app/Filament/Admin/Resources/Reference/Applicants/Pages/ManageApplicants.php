<?php

namespace App\Filament\Admin\Resources\Reference\Applicants\Pages;

use App\Filament\Admin\Resources\Reference\Applicants\ApplicantResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageApplicants extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = ApplicantResource::class;
}
