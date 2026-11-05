<?php

use App\Enums\DelayCause;
use App\Enums\Gender;
use App\Enums\HearingType;
use App\Enums\PartyRole;
use App\Enums\Recording;
use App\Enums\Role;
use App\Enums\StageOutcome;
use App\Enums\Venue;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\LegacyReviewItems\LegacyReviewItemResource;
use App\Filament\Admin\Resources\LegacyReviewItems\Pages\ListLegacyReviewItems;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\LegacyIdMap;
use App\Models\LegacyReviewItem;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\Judge;
use App\Models\Reference\Monitor;
use App\Models\Reference\Nationality;
use App\Models\Reference\Prosecutor;
use App\Models\User;
use App\Services\Research\ResearchQueries;
use App\Support\BlindIndex;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Migration\Fixtures\LegacyFixture;
use Spatie\Activitylog\Models\Activity;

/*
| Proposal B8: the legacy importer, tested against a FAKE legacy database (in-memory
| SQLite built by Migration\Fixtures\LegacyFixture), never against real data.
*/

beforeEach(function () {
    config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);
    DB::purge('legacy');

    $this->reports = sys_get_temp_dir().'/courtwatch-legacy-'.uniqid();
    config(['legacy.reports_path' => $this->reports]);

    $this->fixture = new LegacyFixture('legacy');
    $this->expected = $this->fixture->create(3);

    $user = User::factory()->withMfa()->create(['name' => LegacyFixture::MONITOR_NAME, 'email' => LegacyFixture::MONITOR_EMAIL]);
    $user->assignRole(Role::Monitor->value);
    $this->monitor = Monitor::query()->create(['user_id' => $user->id, 'code' => 'M-LEG']);
});

afterEach(function () {
    File::deleteDirectory($this->reports);
});

function runLegacyImport(array $options = []): void
{
    test()->artisan('legacy:import', ['--connection' => 'legacy', ...$options])->assertSuccessful();
}

/**
 * The case that a legacy row was imported into.
 */
function legacyCase(string $module, int $rowId): CourtCase
{
    $id = LegacyIdMap::query()
        ->where(['legacy_table' => config("legacy.modules.{$module}.table"), 'legacy_key' => (string) $rowId, 'target_type' => 'case'])
        ->value('target_id');

    return CourtCase::query()->findOrFail($id);
}

/**
 * @return array<string, mixed>
 */
function lastLegacyReport(): array
{
    $files = glob(test()->reports.'/reconciliation-*.json') ?: [];
    sort($files);

    return json_decode((string) file_get_contents((string) end($files)), true);
}

/**
 * @return array<string, int>
 */
function importedCounts(): array
{
    return [
        'cases' => CourtCase::withTrashed()->whereNotNull('legacy_module')->count(),
        'parties' => CaseParty::withTrashed()->count(),
        'hearings' => Hearing::withTrashed()->count(),
        'stages' => CaseStage::query()->count(),
        'judges' => Judge::query()->count(),
        'review' => LegacyReviewItem::query()->count(),
        'map' => LegacyIdMap::query()->count(),
    ];
}

it('imports every legacy module and accounts for every row', function () {
    runLegacyImport();

    foreach ($this->expected as $module => $counts) {
        $cases = CourtCase::query()->where('legacy_module', $module);

        expect((clone $cases)->count())->toBe($counts['cases'], "cases of {$module}")
            ->and(Hearing::query()->whereIn('case_id', (clone $cases)->select('id'))->count())->toBe($counts['hearings'], "hearings of {$module}")
            ->and(CaseParty::query()->whereIn('case_id', (clone $cases)->select('id'))->count())->toBe($counts['parties'], "parties of {$module}");
    }

    $report = lastLegacyReport();
    expect($report['balanced'])->toBeTrue()
        ->and($report['dry_run'])->toBeFalse()
        ->and($report['modules']['criminal_corruption']['legacy_rows'])->toBe($this->expected['criminal_corruption']['rows'])
        ->and($report['modules']['criminal_corruption']['rows']['empty'])->toBe(1)
        ->and($report['modules']['criminal_corruption']['rows']['failed'])->toBe(0);

    $rows = array_sum(array_column($this->expected, 'rows'));
    expect(LegacyIdMap::query()->where('target_type', 'case')->count())->toBe($rows - 1); // every row but the empty one
});

it('groups flat rows into cases and removes duplicate reference values', function () {
    runLegacyImport();
    $case = legacyCase('criminal_corruption', 1);

    // Rows 1–6: the first row only had the prosecution number; all spellings point to one court, judge and prosecutor.
    expect(legacyCase('criminal_corruption', 6)->is($case))->toBeTrue()
        ->and($case->type->value)->toBe('criminal_corruption')
        ->and($case->court?->code)->toBe('GJTH-PR')
        ->and($case->department?->code)->toBe('SER')
        ->and($case->judge?->name)->toBe('Besnik Shembulli')
        ->and($case->prosecutor?->name)->toBe('Drita Fiktive')
        ->and($case->prosecutionOffice?->city)->toBe('Prishtina')
        ->and($case->assigned_monitor_id)->toBe($this->monitor->id)
        ->and($case->status)->toBe(WorkflowStatus::Approved)
        ->and($case->approved_at)->not->toBeNull()
        ->and($case->created_at->toDateString())->toBe('2020-02-11')
        ->and($case->court_case_number)->toBe('P.nr. 101/2021')
        ->and($case->indictment_number)->toBe('PP/II.nr.55/2020');

    expect(Judge::query()->where('name', 'ilike', '%shembulli%')->count())->toBe(1)
        ->and(Prosecutor::query()->where('name', 'ilike', '%fiktive%')->count())->toBe(1);

    // "Agim Testaj" and "Testaj Agim" are one person.
    $parties = $case->parties()->get();
    expect($parties)->toHaveCount(2)
        ->and($parties->map(fn (CaseParty $party): string => (string) $party->name)->sort()->values()->all())->toBe(['Agim Testaj', 'Mira Provaj'])
        ->and($parties->pluck('role')->unique()->all())->toBe([PartyRole::Accused]);

    $agim = $parties->first(fn (CaseParty $party): bool => $party->name === 'Agim Testaj');
    expect($agim->gender)->toBe(Gender::Male)
        ->and($agim->nationality?->name_sq)->toBe('Shqiptar')
        ->and($agim->counsel_type?->value)->toBe('retained');

    expect($case->charges()->with('offence.criminalCode')->get()->map(fn ($charge): string => $charge->offence->criminalCode->code.' '.$charge->offence->article_number)->sort()->values()->all())
        ->toBe(['KPRK-2019 414', 'KPRK-2019 415']);

    $sentence = $agim->sentences()->first();
    expect($sentence?->kind->value)->toBe('imposed')
        ->and($sentence?->prison_months)->toBe(24)
        ->and((float) $sentence?->fine_eur)->toBe(1500.0);

    $seizure = $case->measures()->first();
    expect($seizure?->kind->value)->toBe('seizure')->and($seizure?->requested)->toBeTrue()->and($seizure?->granted)->toBeFalse();
});

it('maps free text to codes', function () {
    runLegacyImport();
    $case = legacyCase('criminal_corruption', 1);
    $hearings = $case->hearings()->orderBy('hearing_date')->get();

    expect($hearings->map(fn (Hearing $hearing): string => $hearing->hearing_date->toDateString())->all())->toBe(['2021-03-15', '2021-05-10', '2021-09-01']);

    [$first, $second, $third] = $hearings->all();

    expect($first->hearing_type)->toBe(HearingType::Initial)
        ->and($first->is_public)->toBeTrue()
        ->and($first->was_postponed)->toBeTrue()
        ->and($first->postponement_reasons)->toBe(['prosecutor', 'accused'])
        ->and($first->recording)->toBe(Recording::Audio)
        ->and($first->participants)->toBe(['judge', 'prosecutor', 'accused'])
        ->and($first->monitor_id)->toBe($this->monitor->id)
        ->and($first->status)->toBe(WorkflowStatus::Approved)
        ->and($first->presentParties()->count())->toBe(2);

    expect($second->hearing_type)->toBe(HearingType::MainTrial)
        ->and($second->is_public)->toBeTrue()
        ->and($second->started_late)->toBeTrue()
        ->and($second->delay_minutes)->toBe(45)
        ->and($second->delay_cause)->toBe(DelayCause::Panel)
        ->and($second->was_postponed)->toBeFalse()
        ->and($second->recording)->toBe(Recording::AudioVideo)
        ->and($second->notes)->toContain('vonesë');

    expect($third->hearing_type)->toBe(HearingType::JudgmentAnnouncement);

    $stages = $case->stages()->get()->mapWithKeys(fn (CaseStage $stage): array => [$stage->stage->value => [$stage->date?->toDateString(), $stage->outcome?->value]])->all();
    expect($stages)->toBe([
        'criminal_report_filed' => ['2020-02-10', null],
        'indictment_filed' => ['2021-01-20', null],
        'judgment_announced' => ['2021-09-01', StageOutcome::Acquitting->value],
    ]);

    // Row 9: a court branch, a department alias and stage outcomes.
    $branchCase = legacyCase('criminal_corruption', 9);
    expect($branchCase->court?->code)->toBe('GJTH-PR')
        ->and($branchCase->courtBranch?->city)->toBe('Podujevë')
        ->and($branchCase->department?->code)->toBe('SER')
        ->and($branchCase->judge_id)->toBe($case->judge_id);

    $outcomes = $branchCase->stages()->get()->mapWithKeys(fn (CaseStage $stage): array => [$stage->stage->value => $stage->outcome?->value])->all();
    expect($outcomes)->toMatchArray(['indictment_decision' => 'approved', 'plea_agreement' => 'accepted', 'indictment_filed' => null]);

    $hearing = $branchCase->hearings()->firstOrFail();
    expect($hearing->hearing_type)->toBe(HearingType::Second)
        ->and($hearing->venue)->toBe(Venue::Office)
        ->and($hearing->is_public)->toBeFalse()
        ->and($hearing->interpretation_needed)->toBeTrue()
        ->and($hearing->interpretation_provided)->toBeFalse();
});

it('imports civil and administrative details', function () {
    runLegacyImport();

    $civil = legacyCase('civil', 1);
    $details = $civil->civilDetails;
    expect($civil->court?->code)->toBe('GJTH-GJ')
        ->and($civil->parties()->pluck('role')->map->value->sort()->values()->all())->toBe(['plaintiff', 'respondent'])
        ->and($details?->claim_type?->value)->toBe('property')
        ->and((float) $details?->claim_value_eur)->toBe(12500.0)
        ->and($details?->plaintiff_type?->value)->toBe('natural_person')
        ->and($details?->respondent_type?->value)->toBe('legal_person')
        ->and($details?->representation?->value)->toBe('lawyer')
        ->and($details?->legal_aid)->toBeFalse()
        ->and($details?->mediation_attempted)->toBeTrue()
        ->and($details?->outcome?->value)->toBe('granted')
        ->and($details?->decision_date?->toDateString())->toBe('2022-11-20')
        ->and($civil->hearings()->count())->toBe(2)
        ->and($civil->hearings()->orderBy('hearing_date')->first()?->postponement_reasons)->toBe(['defence_lawyer']);

    $administrative = legacyCase('administrative', 1);
    $details = $administrative->administrativeDetails;
    expect($administrative->department?->code)->toBe('ADM')
        ->and($details?->respondent_body)->toBe('Ministria Fiktive')
        ->and($details?->decision_reasoned)->toBeFalse()
        ->and($details?->legal_remedy_type?->value)->toBe('against_decision')
        ->and($details?->second_instance_outcome?->value)->toBe('rejected')
        ->and($details?->conflict_filed)->toBeTrue()
        ->and($details?->representation?->value)->toBe('self')
        ->and($details?->first_instance_decision_date?->toDateString())->toBe('2020-12-01');

    // Modules without their own department get the module default.
    expect(legacyCase('economic', 1)->department?->code)->toBe('COM');
});

it('sends everything it cannot map to review instead of dropping it', function () {
    runLegacyImport();
    $items = LegacyReviewItem::query()->where('legacy_module', 'criminal_corruption')->get();
    $find = fn (string $reason, string $field) => $items->first(fn (LegacyReviewItem $item): bool => $item->reason === $reason && $item->field === $field);

    expect($find('unknown_reference', 'court')?->value)->toBe('Gjykata e Hënës')
        ->and($find('unmapped_value', 'hearing_type')?->value)->toBe('seancë e çuditshme')
        ->and($find('unmapped_value', 'postponement_reasons')?->value)->toBe('pa arsye')
        ->and($find('new_reference_value', 'article')?->value)->toBe('KPRK-2019 art. 999')
        ->and($find('new_reference_value', 'party_nationality')?->value)->toBe('Marsian')
        ->and($find('conflicting_values', 'stage.judgment_announced.outcome')?->value)->toBe('Dënues | Lirues')
        ->and($find('stage_chronology', 'stages'))->not->toBeNull()
        ->and($find('empty_row', '*')?->legacy_id)->toBe('10');

    // An invalid hearing date keeps all the answers of that hearing for manual entry.
    $invalid = $find('invalid_date', 'hearing_date');
    expect($invalid?->value)->toBe('2022-02-30')
        ->and($invalid?->payload['hearing']['hearing_type'] ?? null)->toBe('Fjala përfundimtare');

    // Columns the mapping does not know: names in clear, values encrypted.
    $unmapped = $find('unmapped_columns', '*');
    expect($unmapped?->value)->toBe(LegacyFixture::UNMAPPED_COLUMN)
        ->and($unmapped?->payload['columns'][LegacyFixture::UNMAPPED_COLUMN] ?? null)->toBe('vlerë e vjetër');

    // The case with an unknown court is still imported, with the company as a legal entity.
    $case = legacyCase('criminal_corruption', 8);
    $party = $case->parties()->firstOrFail();
    expect($case->court_id)->toBeNull()
        ->and($party->role)->toBe(PartyRole::LegalEntity)
        ->and($party->gender)->toBe(Gender::LegalPerson)
        ->and($case->hearings()->count())->toBe(1);

    // New reference values are created inactive, ready to be merged.
    expect(CriminalOffence::query()->where('article_number', '999')->value('is_active'))->toBeFalse()
        ->and(Nationality::query()->where('name_sq', 'Marsian')->value('is_active'))->toBeFalse();
});

it('keeps personal data encrypted and out of clear-text review values', function () {
    runLegacyImport();

    $stored = DB::table('case_parties')->pluck('name')->all();
    expect(implode(' ', $stored))->not->toContain('Testaj')->not->toContain('Provaj');

    // The blind index still finds the party by name.
    expect(CaseParty::query()->where('name_index', BlindIndex::fullName('Agim Testaj'))->exists())->toBeTrue();

    $monitorItem = LegacyReviewItem::query()->where('reason', 'unknown_reference')->where('field', 'monitor')->firstOrFail();
    expect($monitorItem->value)->toBeNull()
        ->and($monitorItem->payload['value'] ?? null)->toBe('Monitor I Panjohur')
        ->and((string) DB::table('legacy_review_items')->where('id', $monitorItem->id)->value('payload'))->not->toContain('Panjohur');

    $values = LegacyReviewItem::query()->pluck('value')->filter()->implode(' | ');
    foreach (['Agim', 'Mira', 'Driton', 'Testaj', 'Provaj', 'Shembulli', 'Fiktive SH.P.K', 'Panjohur'] as $name) {
        expect($values)->not->toContain($name);
    }

    expect((string) DB::table('legacy_review_items')->pluck('payload')->implode(' '))->not->toContain('Testaj');
});

it('is idempotent: a second run changes nothing', function () {
    runLegacyImport();
    $counts = importedCounts();

    runLegacyImport();

    expect(importedCounts())->toBe($counts);

    $report = lastLegacyReport();
    $corruption = $report['modules']['criminal_corruption'];
    expect($report['balanced'])->toBeTrue()
        ->and($corruption['rows']['imported'])->toBe(0)
        ->and($corruption['rows']['already_imported'])->toBe($this->expected['criminal_corruption']['rows'] - 1)
        ->and($corruption['created_this_run'])->toBe([]);
});

it('adds new legacy rows on a re-run without overwriting imported data', function () {
    runLegacyImport();
    $case = legacyCase('criminal_corruption', 1);
    $case->forceFill(['court_case_number' => 'P.nr. 101/2021 (korrigjuar)'])->save();
    $hearing = $case->hearings()->orderBy('hearing_date')->firstOrFail();
    $hearing->forceFill(['notes' => 'Korrigjuar nga redaktori'])->save();

    DB::connection('legacy')->statement('PRAGMA query_only = OFF');
    $this->fixture->append('criminal_corruption', [
        ['court_case_number' => 'P.nr. 101/2021', 'court' => 'Gjykata Themelore Prishtinë', 'party_name' => 'Agim Testaj', 'hearing_date' => '2021-11-20', 'hearing_type' => 'seanca e apelit', 'stage.appeal_filed.date' => '2021-10-01', 'stage.appeal_filed.appellant' => 'Prokurori'],
        ['court_case_number' => 'P.nr. 404/2024', 'court' => 'Gjykata Themelore Ferizaj', 'party_name' => 'Ilir Provaj', 'hearing_date' => '2024-02-02', 'hearing_type' => 'seanca fillestare'],
    ]);

    runLegacyImport();

    $case->refresh();
    expect($case->hearings()->count())->toBe(4)
        ->and($case->parties()->count())->toBe(2)
        ->and($case->court_case_number)->toBe('P.nr. 101/2021 (korrigjuar)')
        ->and($hearing->fresh()->notes)->toBe('Korrigjuar nga redaktori')
        ->and($case->stages()->where('stage', 'appeal_filed')->first()?->appellant?->value)->toBe('prosecutor');

    $new = legacyCase('criminal_corruption', $this->expected['criminal_corruption']['rows'] + 2);
    expect($new->court?->code)->toBe('GJTH-FE')->and($new->is($case))->toBeFalse();

    $report = lastLegacyReport()['modules']['criminal_corruption'];
    expect($report['rows']['imported'])->toBe(2)->and($report['balanced'])->toBeTrue();
});

it('never writes to the legacy database', function () {
    $before = DB::connection('legacy')->table('lendet_korrupsioni')->count();

    runLegacyImport();

    expect(DB::connection('legacy')->table('lendet_korrupsioni')->count())->toBe($before);
    expect(fn () => DB::connection('legacy')->table('lendet_korrupsioni')->insert(['nr_lendes' => 'X']))->toThrow(QueryException::class);
});

it('saves nothing in a dry run but still reports', function () {
    test()->artisan('legacy:import', ['--connection' => 'legacy', '--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    expect(CourtCase::query()->whereNotNull('legacy_module')->count())->toBe(0)
        ->and(LegacyIdMap::query()->count())->toBe(0)
        ->and(LegacyReviewItem::query()->count())->toBe(0)
        ->and(Judge::query()->where('name', 'ilike', '%shembulli%')->exists())->toBeFalse();

    $report = lastLegacyReport();
    expect($report['dry_run'])->toBeTrue()
        ->and($report['balanced'])->toBeTrue()
        ->and($report['modules']['criminal_corruption']['created_this_run']['cases'])->toBe($this->expected['criminal_corruption']['cases']);

    expect(glob($this->reports.'/*-dry-run.md'))->toHaveCount(1);
});

it('isolates a case that fails and imports it on the next run', function () {
    $fail = true;
    CourtCase::creating(function (CourtCase $case) use (&$fail): void {
        if ($fail && $case->court_case_number === 'P.nr. 202/2022') {
            throw new RuntimeException('Simulated failure');
        }
    });

    test()->artisan('legacy:import', ['--connection' => 'legacy'])
        ->expectsOutputToContain('could not be imported')
        ->assertSuccessful();

    $failed = LegacyReviewItem::query()->where('reason', 'import_failed')->firstOrFail();
    expect($failed->value)->toBe('RuntimeException')
        ->and($failed->payload['error'] ?? null)->toBe('Simulated failure')
        ->and($failed->payload['rows'] ?? null)->toBe(['7', '8'])
        ->and(lastLegacyReport()['modules']['criminal_corruption']['rows']['failed'])->toBe(2)
        ->and(lastLegacyReport()['balanced'])->toBeTrue()
        // Records created inside the failed case were rolled back with it.
        ->and(CriminalOffence::query()->where('article_number', '999')->exists())->toBeFalse()
        ->and(CourtCase::query()->where('legacy_module', 'criminal_corruption')->count())->toBe($this->expected['criminal_corruption']['cases'] - 1);

    $fail = false;
    runLegacyImport();

    expect(legacyCase('criminal_corruption', 7)->court_case_number)->toBe('P.nr. 202/2022')
        ->and(CriminalOffence::query()->where('article_number', '999')->count())->toBe(1)
        ->and(CourtCase::query()->where('legacy_module', 'criminal_corruption')->count())->toBe($this->expected['criminal_corruption']['cases']);
});

it('refuses a mapping that does not match the legacy database', function () {
    config(['legacy.modules.civil.columns.court' => 'kolona_qe_nuk_ekziston']);

    test()->artisan('legacy:import', ['--connection' => 'legacy'])
        ->expectsOutputToContain('kolona_qe_nuk_ekziston')
        ->assertFailed();

    expect(CourtCase::query()->whereNotNull('legacy_module')->count())->toBe(0);
});

it('explains a missing legacy connection or module', function () {
    test()->artisan('legacy:import', ['--connection' => 'nowhere'])->expectsOutputToContain('is not configured')->assertFailed();
    test()->artisan('legacy:import', ['--connection' => 'legacy', '--module' => ['nonsense']])->expectsOutputToContain('Unknown legacy module')->assertFailed();
});

it('imports only some modules or the first cases when asked', function () {
    runLegacyImport(['--module' => ['civil'], '--limit' => 2]);

    expect(CourtCase::query()->where('legacy_module', 'civil')->count())->toBe(2)
        ->and(CourtCase::query()->where('legacy_module', '!=', 'civil')->count())->toBe(0);

    $civil = lastLegacyReport()['modules']['civil'];
    expect($civil['rows']['not_read'])->toBeGreaterThan(0)->and($civil['balanced'])->toBeTrue();
});

it('lists legacy tables and unmapped columns without showing values', function () {
    test()->artisan('legacy:inspect', ['--connection' => 'legacy'])
        ->expectsOutputToContain('lendet_korrupsioni')
        ->expectsOutputToContain(LegacyFixture::UNMAPPED_COLUMN)
        ->doesntExpectOutputToContain('vlerë e vjetër')
        ->doesntExpectOutputToContain('Testaj')
        ->assertSuccessful();
});

it('makes imported records count as verified data', function () {
    runLegacyImport();

    $imported = CourtCase::query()->whereNotNull('legacy_module')->count();
    expect(CourtCase::query()->verified()->whereNotNull('legacy_module')->count())->toBe($imported)
        ->and(ResearchQueries::cases()->count())->toBe($imported);
});

it('imports as submitted when configured, for editors to review', function () {
    config(['legacy.status' => 'submitted']);
    runLegacyImport(['--module' => ['administrative']]);

    expect(CourtCase::query()->where('legacy_module', 'administrative')->pluck('status')->unique()->all())->toBe([WorkflowStatus::Submitted]);
});

it('writes one audit entry per run instead of one per record', function () {
    $before = Activity::query()->count();

    runLegacyImport();

    expect(Activity::query()->count())->toBe($before + 1);

    $entry = Activity::query()->latest('id')->firstOrFail();
    expect($entry->log_name)->toBe('legacy')
        ->and($entry->event)->toBe('imported')
        ->and($entry->properties['modules']['civil']['legacy_rows'] ?? null)->toBe($this->expected['civil']['rows']);
});

it('lets editors work through the review items', function () {
    runLegacyImport();
    $editor = actingAsRole(Role::Editor);
    $open = LegacyReviewItem::query()->whereNull('resolved_at')->get();
    $court = $open->firstOrFail(fn (LegacyReviewItem $item): bool => $item->field === 'court');

    Livewire::test(ListLegacyReviewItems::class)
        ->assertCanSeeTableRecords($open->sortBy('id')->take(10))
        ->assertCountTableRecords($open->count())
        ->callAction(TestAction::make('resolve')->table($court), data: ['note' => 'Gjykata u korrigjua'])
        ->assertHasNoActionErrors();

    $court->refresh();
    expect($court->resolved_at)->not->toBeNull()
        ->and($court->resolved_by)->toBe($editor->id)
        ->and($court->resolution_note)->toBe('Gjykata u korrigjua')
        ->and(Activity::query()->where('log_name', 'legacy')->where('subject_id', $court->id)->exists())->toBeTrue();

    // Resolved items leave the default (open) list.
    Livewire::test(ListLegacyReviewItems::class)->assertCanNotSeeTableRecords([$court]);

    // The details (decrypted) are shown to staff who may review, with a link to the case.
    $monitorItem = $open->firstOrFail(fn (LegacyReviewItem $item): bool => $item->field === 'monitor');
    $this->get(LegacyReviewItemResource::getUrl('view', ['record' => $monitorItem]))
        ->assertOk()
        ->assertSee('Monitor I Panjohur');

    expect(LegacyReviewItemResource::caseOf($monitorItem)?->is(legacyCase('criminal_corruption', 7)))->toBeTrue();
});

it('keeps the review items from analysts and monitors', function (Role $role) {
    runLegacyImport();
    $this->actingAs(userWithRole($role));

    $this->get(LegacyReviewItemResource::getUrl())->assertForbidden();
})->with([Role::Analyst, Role::Monitor]);
