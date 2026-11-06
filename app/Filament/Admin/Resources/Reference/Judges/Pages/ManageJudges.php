<?php

namespace App\Filament\Admin\Resources\Reference\Judges\Pages;

use App\Filament\Admin\Resources\Reference\Judges\JudgeResource;
use App\Filament\Admin\Resources\Reference\ManagesReferenceRecords;
use Filament\Resources\Pages\ManageRecords;

class ManageJudges extends ManageRecords
{
    use ManagesReferenceRecords;

    protected static string $resource = JudgeResource::class;
}
