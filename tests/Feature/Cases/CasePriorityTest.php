<?php

use App\Enums\CasePriority;
use App\Enums\CaseType;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Resources\Cases\Pages\CreateCourtCase;
use App\Filament\Admin\Resources\Cases\Pages\ListCourtCases;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Filament\Admin\Widgets\Review\CasesAwaitingReview;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Services\Export\ExportColumns;
use App\Services\Export\ResearchExportColumns;
use App\Services\WorkflowService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/*
| Case priority: planning metadata set by editors. It orders the staff lists, never leaves the staff panel
| (no portal, researcher area or API) and is not part of the reviewed record.
*/

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Notification::fake();
});

it('is normal for a new case', function () {
    expect(CourtCase::factory()->create()->fresh()->priority)->toBe(CasePriority::Normal);
});

it('lets an editor set the priority and its reason when creating a case', function () {
    $court = basicCourt();
    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(CreateCourtCase::class)
        ->fillForm([
            'type' => CaseType::CriminalCorruption->value,
            'court_id' => $court->id,
            'court_case_number' => 'PKR.nr.501/2026',
            'priority' => CasePriority::High->value,
            'priority_note' => 'Interes i lartë publik.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $case = CourtCase::query()->where('court_case_number', 'PKR.nr.501/2026')->sole();

    expect($case->priority)->toBe(CasePriority::High)
        ->and($case->priority_note)->toBe('Interes i lartë publik.');
});

it('does not let a monitor set the priority, in the form or anywhere else', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $this->actingAs($user);

    Livewire::test(CreateCourtCase::class)
        ->assertFormFieldDisabled('priority')
        ->fillForm([
            'type' => CaseType::CriminalGeneral->value,
            'court_id' => $court->id,
            'court_case_number' => 'PKR.nr.502/2026',
            'priority' => CasePriority::High->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $case = CourtCase::query()->where('court_case_number', 'PKR.nr.502/2026')->sole();

    expect($case->priority)->toBe(CasePriority::Normal)
        ->and($user->can('prioritize', CourtCase::class))->toBeFalse();

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])->assertActionHidden('setPriority');
    Livewire::test(ListCourtCases::class)->assertActionHidden(TestAction::make('setPriority')->table()->bulk());
});

it('lets analysts and researchers see but never change the priority', function (Role $role) {
    expect(userWithRole($role, mfa: $role !== Role::Researcher)->can('prioritize', CourtCase::class))->toBeFalse();
})->with([Role::Analyst, Role::Researcher]);

it('lets an editor change the priority of a published case from the case page', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->status(WorkflowStatus::Published)->create();

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])
        ->assertActionVisible('setPriority')
        ->callAction('setPriority', data: ['priority' => CasePriority::High->value, 'priority_note' => '  I akuzuari në paraburgim.  '])
        ->assertHasNoActionErrors()
        ->assertNotified(trans_choice('cases.priority.done', 1));

    $case->refresh();

    // Only the priority changed: the published record stays published.
    expect($case->priority)->toBe(CasePriority::High)
        ->and($case->priority_note)->toBe('I akuzuari në paraburgim.')
        ->and($case->status)->toBe(WorkflowStatus::Published);
});

it('drops the reason when a case goes back to normal priority', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create(['priority' => CasePriority::High, 'priority_note' => 'Arsye']);

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])
        ->callAction('setPriority', data: ['priority' => CasePriority::Normal->value, 'priority_note' => 'Arsye'])
        ->assertHasNoActionErrors();

    expect($case->fresh()->priority)->toBe(CasePriority::Normal)
        ->and($case->fresh()->priority_note)->toBeNull();
});

it('sets the priority of several cases at once from the list', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $cases = CourtCase::factory()->count(3)->create();

    Livewire::test(ListCourtCases::class)
        ->selectTableRecords($cases->take(2)->pluck('id')->all())
        ->callAction(TestAction::make('setPriority')->table()->bulk(), data: ['priority' => CasePriority::Low->value, 'priority_note' => 'Procedura është pezulluar.'])
        ->assertHasNoActionErrors()
        ->assertNotified(trans_choice('cases.priority.done', 2, ['count' => 2]));

    expect($cases->map(fn (CourtCase $case): CasePriority => $case->fresh()->priority)->all())
        ->toBe([CasePriority::Low, CasePriority::Low, CasePriority::Normal]);
});

it('lists high-priority cases first, then the most recently changed', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $low = CourtCase::factory()->create(['priority' => CasePriority::Low, 'updated_at' => now()]);
    $recent = CourtCase::factory()->create(['updated_at' => now()->subDay()]);
    $older = CourtCase::factory()->create(['updated_at' => now()->subDays(5)]);
    $high = CourtCase::factory()->create(['priority' => CasePriority::High, 'updated_at' => now()->subDays(30)]);

    Livewire::test(ListCourtCases::class)->assertCanSeeTableRecords([$high, $recent, $older, $low], inOrder: true);
});

it('filters the case list by priority', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $high = CourtCase::factory()->create(['priority' => CasePriority::High]);
    $normal = CourtCase::factory()->create();

    Livewire::test(ListCourtCases::class)
        ->filterTable('priority', [CasePriority::High->value])
        ->assertCanSeeTableRecords([$high])
        ->assertCanNotSeeTableRecords([$normal]);
});

it('puts high-priority submissions first in the review queue', function () {
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);
    $workflow = app(WorkflowService::class);

    $first = CourtCase::factory()->create();
    $workflow->submit($first, $editor);
    $this->travel(1)->hour();
    $urgent = CourtCase::factory()->create(['priority' => CasePriority::High]);
    $workflow->submit($urgent, $editor);

    Livewire::test(CasesAwaitingReview::class)->assertCanSeeTableRecords([$urgent->fresh(), $first->fresh()], inOrder: true);
});

it('keeps the priority out of the reviewer\'s diff but in the audit log', function () {
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);
    $workflow = app(WorkflowService::class);
    $case = CourtCase::factory()->create();
    $workflow->submit($case, $editor);
    $workflow->returnWithComment($case->fresh(), $editor, 'Duhet plotësuar.');

    $case->fresh()->forceFill(['priority' => CasePriority::High, 'priority_note' => 'Arsye'])->save();
    $workflow->submit($case->fresh(), $editor);

    $fields = collect($workflow->diff($case->fresh())['changes'])->pluck('field');
    $logged = Activity::query()->where('subject_type', $case->getMorphClass())->where('subject_id', $case->id)
        ->get()->contains(fn (Activity $activity): bool => ($activity->properties['attributes']['priority'] ?? null) === 'high');

    expect($fields)->not->toContain('priority')->not->toContain('priority_note')
        ->and($logged)->toBeTrue();
});

it('shows high-priority cases first in the monitor app, with a label', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $recent = CourtCase::factory()->create(['court_id' => $court->id, 'updated_at' => now()]);
    $high = CourtCase::factory()->create(['court_id' => $court->id, 'priority' => CasePriority::High, 'updated_at' => now()->subMonth()]);

    $cases = collect($this->actingAs($user)->getJson('/m/api/bootstrap')->assertOk()->json('cases'));

    expect($cases->pluck('id')->take(2)->all())->toBe([$high->id, $recent->id])
        ->and($cases->firstWhere('id', $high->id)['priority_label'])->toBe(CasePriority::High->getLabel())
        ->and($cases->firstWhere('id', $recent->id)['priority'])->toBeNull();
});

it('exports the priority for staff only', function () {
    $priority = __('cases.fields.priority');

    expect(array_keys(ExportColumns::cases()))->toContain($priority)
        ->and(array_keys(ResearchExportColumns::cases()))->not->toContain($priority);
});

it('never shows the priority on the public portal or in the researcher API', function () {
    $case = CourtCase::factory()->create(['court_id' => basicCourt()->id, 'priority' => CasePriority::High, 'priority_note' => 'Arsyeja-e-brendshme-xyz']);
    $case->forceFill(['status' => WorkflowStatus::Published, 'approved_at' => now(), 'published_at' => now()])->save();
    CaseParty::factory()->create(['case_id' => $case->id]);

    $page = (string) $this->get('/sq/cases/'.$case->public_ref)->assertOk()->getContent();

    $token = researcher()->createToken('test', ['research:read'])->plainTextToken;
    $api = $this->withToken($token)->getJson('/api/v1/cases')->assertOk();

    expect($page)->not->toContain('Arsyeja-e-brendshme-xyz')->not->toContain(__('cases.fields.priority'))
        ->and($api->getContent())->not->toContain('Arsyeja-e-brendshme-xyz')->not->toContain('priority');
});
