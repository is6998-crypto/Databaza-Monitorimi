<?php

use App\Enums\Role;
use App\Filament\Admin\Resources\Reference\CourtBranches\CourtBranchResource;
use App\Filament\Admin\Resources\Reference\CriminalOffences\CriminalOffenceResource;
use App\Filament\Admin\Resources\Reference\CriminalOffences\Pages\ManageCriminalOffences;
use App\Filament\Admin\Resources\Reference\Nationalities\NationalityResource;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Nationality;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

function csvFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $contents);

    return $path;
}

it('creates new rows and updates existing ones by name', function () {
    actingAsRole(Role::Editor);
    $existing = Nationality::query()->where('name_sq', 'Shqiptar')->firstOrFail();

    $result = NationalityResource::csvImporter()->import(csvFile("name_sq,name_en,name_sr,is_active\nShqiptar,Albanian (updated),Albanac,1\nAshkali i ri,New Ashkali,,yes\n"));

    expect($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(1)
        ->and($existing->fresh()->name_en)->toBe('Albanian (updated)')
        ->and($existing->fresh()->name_sr)->toBe('Albanac')
        ->and(Nationality::query()->where('name_sq', 'Ashkali i ri')->exists())->toBeTrue();
});

it('resolves related records by code or name and reports unknown values', function () {
    actingAsRole(Role::Editor);

    $result = CourtBranchResource::csvImporter()->import(csvFile("name_sq;name_en;court;city\nDega Test;Test branch;GJTH-PR;Obiliq\nDega e Humbur;Lost;Gjykata që s'ekziston;X\n"));

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['errors'][0])->toContain('court')
        ->and(CourtBranch::query()->where('name_sq', 'Dega Test')->value('city'))->toBe('Obiliq');
});

it('validates rows and skips invalid ones', function () {
    actingAsRole(Role::Editor);

    $result = CriminalOffenceResource::csvImporter()->import(csvFile("criminal_code,article_number,title_sq,title_en,category\nKPRK-2019,999,Vepër testuese,Test offence,Korrupsion\nKPRK-2019,,Pa nen,No article,\n"));

    expect($result['created'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and(CriminalOffence::query()->where('article_number', '999')->first()?->category?->name_sq)->toBe('Korrupsion');
});

it('rejects files without the required columns', function () {
    actingAsRole(Role::Editor);

    NationalityResource::csvImporter()->import(csvFile("foo,bar\n1,2\n"));
})->throws(RuntimeException::class);

it('imports through the Filament action', function () {
    Filament::setCurrentPanel('admin');
    actingAsRole(Role::Editor);

    $file = UploadedFile::fake()->createWithContent('offences.csv', "criminal_code,article_number,title_sq,title_en\nKPK-2012,998,Test nga paneli,From the panel\n");

    Livewire::test(ManageCriminalOffences::class)
        ->callAction('importCsv', data: ['file' => $file])
        ->assertHasNoActionErrors();

    expect(CriminalOffence::query()->where('article_number', '998')->exists())->toBeTrue();
});
