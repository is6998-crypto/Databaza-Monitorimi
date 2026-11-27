<?php

use App\Enums\Role;
use App\Models\Reference\Court;
use App\Models\Reference\CourtBranch;
use App\Models\Reference\Department;

/*
| Serbian (Latin script): the staff panel, Filament's own strings, dates and the court names.
*/

it('switches the staff panel to Serbian, Filament\'s own strings included', function () {
    $editor = userWithRole(Role::Editor);

    $this->actingAs($editor)->get('/locale/sr')->assertRedirect();

    expect($editor->fresh()->locale)->toBe('sr');

    $this->get('/admin/cases')
        ->assertOk()
        ->assertSee('Predmeti')          // our own string
        ->assertSee('Odjavi se');        // Filament's, from its sr_Latn files
});

it('keeps our own overrides above the package strings and leaves other languages alone', function () {
    app()->setLocale('sr');
    expect(__('filament-panels::layout.actions.logout.label'))->toBe('Odjavi se')
        ->and(trans_choice('deadlines.units.months', 3, ['count' => 3]))->toBe('3 meseca')
        ->and(trans_choice('deadlines.units.years', 5, ['count' => 5]))->toBe('5 godina');

    app()->setLocale('sq');
    expect(__('filament-panels::layout.actions.logout.label'))->not->toBe('Odjavi se');
});

it('names the courts, branches and departments in Serbian', function () {
    app()->setLocale('sr');

    expect(Court::query()->where('code', 'GJTH-PR')->firstOrFail()->label())->toBe('Osnovni sud u Prištini')
        ->and(Court::query()->where('code', 'GJA')->firstOrFail()->label())->toBe('Apelacioni sud')
        ->and(CourtBranch::query()->where('name_sq', 'Dega në Fushë Kosovë')->firstOrFail()->label())->toBe('Ogranak u Kosovu Polju')
        ->and(Department::query()->where('code', 'JUV')->firstOrFail()->label())->toBe('Odeljenje za maloletnike');
});
