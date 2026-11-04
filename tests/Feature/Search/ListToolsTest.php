<?php

use App\Enums\CaseType;
use App\Enums\ExportFormat;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\Pages\ListCourtCases;
use App\Filament\Admin\Resources\Hearings\Pages\ListHearings;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\SavedFilter;
use App\Services\Export\ExportColumns;
use App\Services\Export\ExportService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

it('keeps filters in the URL and saves them under a name', function () {
    $user = userWithRole(Role::Editor);
    $this->actingAs($user);

    $this->get('/admin/cases?filters[type][values][0]=civil')->assertOk();

    Livewire::withQueryParams(['filters' => ['type' => ['values' => ['civil']]], 'search' => 'PKR'])
        ->test(ListCourtCases::class)
        ->assertSet('tableFilters.type.values', ['civil'])
        ->callAction('saveFilters', data: ['name' => 'Civile', 'is_shared' => true])
        ->assertHasNoActionErrors();

    $saved = SavedFilter::query()->where('user_id', $user->id)->firstOrFail();

    expect($saved->resource)->toBe('cases')
        ->and($saved->filters['filters']['type']['values'])->toBe(['civil'])
        ->and($saved->filters['search'])->toBe('PKR');
});

it('shows shared saved filters to colleagues but only lets the owner delete them', function () {
    $owner = userWithRole(Role::Editor);
    $colleague = userWithRole(Role::Analyst);
    $filter = SavedFilter::query()->create(['user_id' => $owner->id, 'resource' => 'cases', 'name' => 'Shared', 'is_shared' => true, 'filters' => ['filters' => ['status' => ['values' => ['approved']]]]]);
    SavedFilter::query()->create(['user_id' => $owner->id, 'resource' => 'cases', 'name' => 'Private', 'is_shared' => false, 'filters' => []]);

    expect(SavedFilter::query()->availableTo($colleague, 'cases')->pluck('name')->all())->toBe(['Shared'])
        ->and($colleague->can('delete', $filter))->toBeFalse()
        ->and($owner->can('delete', $filter))->toBeTrue();

    $this->actingAs($colleague);
    Livewire::test(ListCourtCases::class)->assertActionVisible('applySavedFilter'.$filter->id);
});

it('exports the filtered cases and logs the export', function () {
    $user = userWithRole(Role::Analyst);
    $this->actingAs($user);
    $civil = CourtCase::factory()->type(CaseType::Civil)->count(2)->create();
    CourtCase::factory()->type(CaseType::CriminalGeneral)->create();
    CaseParty::factory()->create(['case_id' => $civil[0]->id, 'name' => 'Arbnore Kodra']);

    Livewire::withQueryParams(['filters' => ['type' => ['values' => ['civil']]]])
        ->test(ListCourtCases::class)
        ->callAction('exportFiltered', data: ['format' => 'csv'])
        ->assertFileDownloaded();

    $log = ExportLog::query()->latest('id')->firstOrFail();

    expect($log->user_id)->toBe($user->id)
        ->and($log->context)->toBe('admin')
        ->and($log->resource)->toBe('cases')
        ->and($log->row_count)->toBe(2)
        ->and($log->filters['filters']['type']['values'])->toBe(['civil']);
});

it('never puts party names in export files', function () {
    $case = CourtCase::factory()->create(['court_case_number' => 'PKR.nr.1/2026']);
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Arbnore Kodra']);

    $response = app(ExportService::class)->stream('cases', CourtCase::query()->with(ExportColumns::caseRelations()), ExportColumns::cases(), ExportFormat::Csv, 'admin', null);
    $csv = $response->getContent() ?: (function () use ($response) {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    })();

    expect($csv)->toContain('PKR.nr.1/2026')
        ->not->toContain('Arbnore')
        ->not->toContain('Kodra');
});

it('exports selected hearings', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $hearings = Hearing::factory()->count(3)->create();

    Livewire::test(ListHearings::class)
        ->selectTableRecords($hearings->take(2)->pluck('id')->all())
        ->callAction(TestAction::make('exportSelected')->table()->bulk(), data: ['format' => 'xlsx'])
        ->assertFileDownloaded();

    expect(ExportLog::query()->latest('id')->first()->row_count)->toBe(2);
});

it('does not offer exports to monitors', function () {
    $this->actingAs(monitorUser([basicCourt()->id]));

    Livewire::test(ListCourtCases::class)->assertActionHidden('exportFiltered');
});

it('assigns a monitor to many cases at once', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $monitor = monitorUser([basicCourt()->id])->monitor;
    $cases = CourtCase::factory()->count(3)->create();

    Livewire::test(ListCourtCases::class)
        ->selectTableRecords($cases->pluck('id')->all())
        ->callAction(TestAction::make('assignMonitor')->table()->bulk(), data: ['monitor_id' => $monitor->id])
        ->assertHasNoActionErrors();

    expect(CourtCase::query()->whereKey($cases->pluck('id'))->pluck('assigned_monitor_id')->unique()->all())->toBe([$monitor->id]);
});

it('changes status in bulk, skipping records where it is not allowed', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $submitted = CourtCase::factory()->status(WorkflowStatus::Submitted)->create();
    $draft = CourtCase::factory()->create();

    Livewire::test(ListCourtCases::class)
        ->selectTableRecords([$submitted->id, $draft->id])
        ->callAction(TestAction::make('changeStatus')->table()->bulk(), data: ['status' => 'approved'])
        ->assertHasNoActionErrors();

    expect($submitted->fresh()->status)->toBe(WorkflowStatus::Approved)
        ->and($draft->fresh()->status)->toBe(WorkflowStatus::Draft);
});

it('lets only the super admin delete in bulk, and only softly', function () {
    $cases = CourtCase::factory()->count(2)->create();

    $this->actingAs(userWithRole(Role::Editor));
    Livewire::test(ListCourtCases::class)->assertActionHidden(TestAction::make('delete')->table()->bulk());

    $this->actingAs(userWithRole(Role::SuperAdmin));
    Livewire::test(ListCourtCases::class)
        ->selectTableRecords($cases->pluck('id')->all())
        ->callAction(TestAction::make('delete')->table()->bulk());

    expect(CourtCase::query()->whereKey($cases->pluck('id'))->count())->toBe(0)
        ->and(CourtCase::withTrashed()->whereKey($cases->pluck('id'))->count())->toBe(2);
});

it('lists exports for editors', function () {
    ExportLog::query()->create(['context' => 'public', 'resource' => 'open-data', 'format' => 'csv', 'row_count' => 5]);

    $this->actingAs(userWithRole(Role::Editor))->get('/admin/export-logs')->assertOk()->assertSee('open-data');
    $this->actingAs(monitorUser())->get('/admin/export-logs')->assertForbidden();
});
