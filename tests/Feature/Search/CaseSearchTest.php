<?php

use App\Enums\ChargeStage;
use App\Enums\Role;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Cases\Pages\ListCourtCases;
use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Judge;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function searchCases(string $term): array
{
    return CourtCaseResource::getGlobalSearchResults($term)->map(fn ($result) => $result->url)->all();
}

it('finds cases by part of the case, indictment or police number', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['court_case_number' => 'PKR.nr.4321/2024', 'indictment_number' => 'PP/II.nr.777/2023', 'police_case_number' => '2023-ZZ-9999']);

    foreach (['4321/2024', 'pp/ii.nr.777', 'ZZ-9999'] as $term) {
        expect(searchCases($term))->toContain(CourtCaseResource::getUrl('view', ['record' => $case]));
    }
});

it('tolerates typos in case numbers (pg_trgm)', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['court_case_number' => 'PKR.nr.4321/2024']);
    CourtCase::factory()->create(['court_case_number' => 'PKR.nr.88/2019']);

    $results = searchCases('PKR.nr.4321/2O24');

    expect($results)->toContain(CourtCaseResource::getUrl('view', ['record' => $case]))->toHaveCount(1);
});

it('finds cases by party name through the blind index only', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create();
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Fatmir Zeqiraj']);
    $other = CourtCase::factory()->create();
    CaseParty::factory()->create(['case_id' => $other->id, 'name' => 'Fatmir Berisha']);

    expect(searchCases('zeqiraj'))->toBe([CourtCaseResource::getUrl('view', ['record' => $case])])
        ->and(searchCases('Fatmir Zeqiraj'))->toBe([CourtCaseResource::getUrl('view', ['record' => $case])])
        ->and(searchCases('Fatmir'))->toHaveCount(2);
});

it('finds cases by judge (with typos) and by offence', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $judge = Judge::query()->create(['name' => 'Teuta Gjyqtarja', 'court_id' => basicCourt()->id]);
    $byJudge = CourtCase::factory()->create(['judge_id' => $judge->id]);
    $byOffence = CourtCase::factory()->create();
    $party = CaseParty::factory()->create(['case_id' => $byOffence->id]);
    $offence = CriminalOffence::query()->where('title_en', 'Trading in influence')->firstOrFail();
    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Indictment]);

    expect(searchCases('Teuta Gjyqtrja'))->toContain(CourtCaseResource::getUrl('view', ['record' => $byJudge]))
        ->and(searchCases('Trading in influence'))->toContain(CourtCaseResource::getUrl('view', ['record' => $byOffence]));
});

it('keeps monitors inside their scope when searching', function () {
    $user = monitorUser([basicCourt(0)->id]);
    $mine = CourtCase::factory()->create(['court_id' => basicCourt(0)->id, 'court_case_number' => 'PKR.nr.5555/2025']);
    $theirs = CourtCase::factory()->create(['court_id' => basicCourt(1)->id, 'court_case_number' => 'PKR.nr.5556/2025']);
    $this->actingAs($user);

    $results = searchCases('PKR.nr.555');

    expect($results)->toContain(CourtCaseResource::getUrl('view', ['record' => $mine]))
        ->not->toContain(CourtCaseResource::getUrl('view', ['record' => $theirs]));
});

it('uses the same search in the case list', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create();
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Lirie Tahiri']);
    $other = CourtCase::factory()->create();

    Livewire::test(ListCourtCases::class)
        ->searchTable('Tahiri')
        ->assertCanSeeTableRecords([$case])
        ->assertCanNotSeeTableRecords([$other]);
});
