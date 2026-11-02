<?php

namespace App\Filament\Admin\Resources\Reference\Applicants;

use App\Filament\Admin\Resources\Reference\Applicants\Pages\ManageApplicants;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Applicant;
use Filament\Support\Icons\Heroicon;

class ApplicantResource extends ReferenceResource
{
    protected static ?string $model = Applicant::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'reference/applicants';

    public static function key(): string
    {
        return 'applicants';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApplicants::route('/'),
        ];
    }
}
