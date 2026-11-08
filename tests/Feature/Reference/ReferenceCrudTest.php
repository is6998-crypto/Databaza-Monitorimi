<?php

use App\Enums\CourtLevel;
use App\Enums\Role;
use App\Filament\Admin\Resources\Reference\Courts\Pages\ManageCourts;
use App\Filament\Admin\Resources\Reference\CriminalOffences\Pages\ManageCriminalOffences;
use App\Filament\Admin\Resources\Reference\Judges\Pages\ManageJudges;
use App\Filament\Admin\Resources\Reference\ReferenceResource;
use App\Models\Reference\Court;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Judge;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('seeds the public court structure', function () {
    expect(Court::query()->where('level', CourtLevel::Basic)->count())->toBe(7)
        ->and(Court::query()->where('level', CourtLevel::Appeal)->count())->toBe(1)
        ->and(Court::query()->where('level', CourtLevel::Supreme)->count())->toBe(1)
        ->and(CriminalCode::query()->pluck('code')->all())->toContain('KPRK-2019', 'KPK-2012', 'UNMIK-2003/25')
        ->and(CriminalOffence::query()->count())->toBeGreaterThan(10);
});

it('has a Filament resource for every reference table', function () {
    $resources = collect(File::allFiles(app_path('Filament/Admin/Resources/Reference')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Resource.php') && $file->getFilename() !== 'ReferenceResource.php')
        ->count();

    expect($resources)->toBe(16);
});

it('lists reference data for editors and analysts', function (Role $role) {
    $this->actingAs(userWithRole($role));

    Livewire::test(ManageCourts::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Court::query()->limit(5)->get());
})->with([Role::Editor, Role::Analyst, Role::SuperAdmin]);

it('hides reference management from monitors', function () {
    $this->actingAs(userWithRole(Role::Monitor));

    $this->get('/admin/reference/courts')->assertForbidden();
});

it('lets editors create and update bilingual records', function () {
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(ManageCourts::class)
        ->callAction('create', data: [
            'name_sq' => 'Gjykata Test',
            'name_en' => 'Test Court',
            'name_sr' => 'Test sud',
            'code' => 'TST',
            'level' => CourtLevel::Basic->value,
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    $court = Court::query()->where('code', 'TST')->firstOrFail();
    expect($court->name_sr)->toBe('Test sud')
        ->and($court->label('en'))->toBe('Test Court');

    Livewire::test(ManageCourts::class)
        ->callAction(TestAction::make('edit')->table($court), data: ['name_en' => 'Renamed Court'])
        ->assertHasNoActionErrors();

    expect($court->fresh()->name_en)->toBe('Renamed Court');
});

it('validates required fields', function () {
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(ManageCourts::class)
        ->callAction('create', data: ['name_sq' => '', 'level' => null])
        ->assertHasActionErrors(['name_sq' => 'required', 'level' => 'required']);
});

it('stops analysts from creating or editing', function () {
    $this->actingAs(userWithRole(Role::Analyst));

    Livewire::test(ManageCourts::class)->assertActionHidden('create');
});

it('only lets the super admin delete (soft) and restore', function () {
    $court = Court::query()->create(['name_sq' => 'Për fshirje', 'level' => CourtLevel::Basic]);

    $this->actingAs(userWithRole(Role::Editor));
    Livewire::test(ManageCourts::class)->assertActionHidden(TestAction::make('delete')->table($court));

    $this->actingAs(userWithRole(Role::SuperAdmin));
    Livewire::test(ManageCourts::class)->callAction(TestAction::make('delete')->table($court));

    expect($court->fresh()->trashed())->toBeTrue();
});

it('falls back to Albanian when a translation is missing', function () {
    $court = Court::query()->create(['name_sq' => 'Vetëm shqip', 'level' => CourtLevel::Basic]);

    expect($court->label('en'))->toBe('Vetëm shqip')
        ->and($court->label('sr'))->toBe('Vetëm shqip');
});

it('searches people and offences', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $judge = Judge::query()->create(['name' => 'Testuar Gjyqtari', 'court_id' => Court::query()->value('id')]);

    Livewire::test(ManageJudges::class)
        ->searchTable('Testuar')
        ->assertCanSeeTableRecords([$judge]);

    Livewire::test(ManageCriminalOffences::class)
        ->searchTable('sektorin privat')
        ->assertCanSeeTableRecords(CriminalOffence::query()->where('title_sq', 'ilike', '%sektorin privat%')->get())
        ->assertCountTableRecords(2);
});

it('defines CSV columns for every resource', function () {
    collect(File::allFiles(app_path('Filament/Admin/Resources/Reference')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), 'Resource.php') && $file->getFilename() !== 'ReferenceResource.php')
        ->each(function ($file) {
            $class = 'App\\Filament\\Admin\\Resources\\Reference\\'.basename($file->getPath()).'\\'.$file->getBasename('.php');
            /** @var class-string<ReferenceResource> $class */
            expect($class::csvImporter()->header())->not->toBeEmpty();
        });
});
