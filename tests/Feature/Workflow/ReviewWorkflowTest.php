<?php

use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Pages\ReviewQueue;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;
use App\Models\WorkflowTransition;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Notification::fake();
    $this->workflow = app(WorkflowService::class);
});

function draftCaseOf(User $author): CourtCase
{
    $case = CourtCase::factory()->create(['court_id' => $author->monitor?->courts()->value('courts.id') ?? basicCourt()->id, 'created_by' => $author->id]);
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Arta Shpendi']);

    return $case->fresh();
}

it('lets the author submit a draft and notifies the editors', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = draftCaseOf($monitor);

    $this->workflow->submit($case, $monitor);

    $transition = WorkflowTransition::query()->where('subject_id', $case->id)->firstOrFail();

    expect($case->fresh()->status)->toBe(WorkflowStatus::Submitted)
        ->and($case->fresh()->submitted_at)->not->toBeNull()
        ->and($transition->from_status)->toBe(WorkflowStatus::Draft)
        ->and(json_encode($transition->snapshot))->not->toContain('Arta')
        ->and(Activity::query()->where('log_name', 'workflow')->where('event', 'transition')->exists())->toBeTrue();

    Notification::assertSentTo($editor, WorkflowNotification::class, fn (WorkflowNotification $n) => $n->status === WorkflowStatus::Submitted);
    Notification::assertNotSentTo($monitor, WorkflowNotification::class);
});

it('lets editors approve and notifies the author', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = draftCaseOf($monitor);
    $this->workflow->submit($case, $monitor);

    $this->workflow->startReview($case->fresh(), $editor);
    $this->workflow->approve($case->fresh(), $editor);

    expect($case->fresh()->status)->toBe(WorkflowStatus::Approved)
        ->and($case->fresh()->approved_by)->toBe($editor->id);

    Notification::assertSentTo($monitor, WorkflowNotification::class, fn (WorkflowNotification $n) => $n->status === WorkflowStatus::Approved);
});

it('requires a comment to return a submission', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = draftCaseOf($monitor);
    $this->workflow->submit($case, $monitor);

    expect(fn () => $this->workflow->returnWithComment($case->fresh(), $editor, ''))->toThrow(ValidationException::class);

    $this->workflow->returnWithComment($case->fresh(), $editor, 'Mungon data e aktakuzës.');

    expect($case->fresh()->status)->toBe(WorkflowStatus::Returned)
        ->and($case->fresh()->return_comment)->toBe('Mungon data e aktakuzës.');

    Notification::assertSentTo($monitor, WorkflowNotification::class, fn (WorkflowNotification $n) => $n->comment === 'Mungon data e aktakuzës.');
});

it('lets the author correct a returned record and resubmit, showing what changed', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = draftCaseOf($monitor);
    $this->workflow->submit($case, $monitor);
    $this->workflow->returnWithComment($case->fresh(), $editor, 'Korrigjoni numrin.');

    expect($monitor->can('update', $case->fresh()))->toBeTrue();

    $case->fresh()->update(['court_case_number' => 'PKR.nr.999/2026']);
    $this->workflow->submit($case->fresh(), $monitor);

    $diff = $this->workflow->diff($case->fresh());
    $fields = collect($diff['changes'])->pluck('field');

    expect($diff['base'])->toBe('submitted')
        ->and($fields)->toContain('court_case_number')
        ->and(collect($diff['changes'])->firstWhere('field', 'court_case_number')['new'])->toBe('PKR.nr.999/2026')
        ->and($case->fresh()->return_comment)->toBeNull();
});

it('compares with the last approved version after re-editing an approved record', function () {
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create();
    $this->workflow->submit($case, $editor);
    $this->workflow->approve($case->fresh(), $editor);

    $this->workflow->returnWithComment($case->fresh(), $editor, 'Duhet plotësuar.');
    $case->fresh()->update(['summary' => 'Shënim i ri']);
    $this->workflow->submit($case->fresh(), $editor);

    $diff = $this->workflow->diff($case->fresh());

    expect(collect($diff['changes'])->pluck('field'))->toContain('summary');
});

it('refuses to skip the review', function (WorkflowStatus $to) {
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create();

    $this->workflow->transition($case, $to, $editor);
})->with([WorkflowStatus::Approved, WorkflowStatus::Published, WorkflowStatus::InReview])->throws(ValidationException::class);

it('does not let monitors or analysts review', function (Role $role) {
    $author = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create(['created_by' => $author->id]);
    $this->workflow->submit($case, $author);

    $this->workflow->approve($case->fresh(), userWithRole($role));
})->with([Role::Monitor, Role::Analyst])->throws(AuthorizationException::class);

it('does not let other monitors submit someone else\'s draft', function () {
    $court = basicCourt();
    $author = monitorUser([$court->id]);
    $colleague = monitorUser([$court->id]);
    $case = draftCaseOf($author);

    $this->workflow->submit($case, $colleague);
})->throws(AuthorizationException::class);

it('publishes approved records and freezes them', function () {
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create();
    $this->workflow->submit($case, $editor);
    $this->workflow->approve($case->fresh(), $editor);
    $this->workflow->publish($case->fresh(), $editor);

    expect($case->fresh()->status)->toBe(WorkflowStatus::Published)
        ->and($editor->can('update', $case->fresh()))->toBeFalse();

    $this->workflow->unpublish($case->fresh(), $editor);

    expect($case->fresh()->status)->toBe(WorkflowStatus::Approved);
});

it('applies the same workflow to hearings', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create(['court_id' => basicCourt()->id]);
    $hearing = Hearing::factory()->forCase($case)->create(['created_by' => $monitor->id, 'monitor_id' => $monitor->monitor->id]);

    $this->workflow->submit($hearing, $monitor);
    $this->workflow->approve($hearing->fresh(), $editor);

    expect($hearing->fresh()->status)->toBe(WorkflowStatus::Approved)
        ->and($monitor->can('update', $hearing->fresh()))->toBeFalse();
});

it('approves and returns from the case page', function () {
    $editor = userWithRole(Role::Editor);
    $author = userWithRole(Role::Monitor);
    $case = CourtCase::factory()->create(['created_by' => $author->id]);
    $case->forceFill(['status' => WorkflowStatus::Submitted])->save();
    $this->actingAs($editor);

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])
        ->assertActionVisible('approve')
        ->assertActionHidden('submitForReview')
        ->callAction('returnForCorrection', data: ['comment' => ''])
        ->assertHasActionErrors(['comment' => 'required']);

    expect($case->fresh()->status)->toBe(WorkflowStatus::Submitted);

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])
        ->callAction('approve', data: ['comment' => 'OK'])
        ->assertHasNoActionErrors();

    expect($case->fresh()->status)->toBe(WorkflowStatus::Approved);
});

it('shows the changes to the reviewer', function () {
    $editor = userWithRole(Role::Editor);
    $case = CourtCase::factory()->create(['created_by' => $editor->id]);
    $this->workflow->submit($case, $editor);
    $this->actingAs($editor);

    Livewire::test(ViewCourtCase::class, ['record' => $case->getRouteKey()])
        ->mountAction('viewChanges')
        ->assertActionMounted('viewChanges');

    $html = view('filament.admin.workflow-diff', ['diff' => $this->workflow->diff($case->fresh())])->render();

    expect($html)->toContain(e(__('workflow.diff.first_submission')))
        ->and($html)->toContain('data-workflow-diff');
});

it('opens the review queue for editors only', function () {
    $case = CourtCase::factory()->create();
    $case->forceFill(['status' => WorkflowStatus::Submitted, 'submitted_at' => now()])->save();

    $this->actingAs(userWithRole(Role::Monitor))->get('/admin/review-queue')->assertForbidden();
    $this->actingAs(userWithRole(Role::Editor))->get('/admin/review-queue')->assertOk();

    expect(ReviewQueue::getNavigationBadge())->toBe('1');
});

it('puts reviewer notifications in the panel bell without personal names', function () {
    Notification::fake();
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $case = draftCaseOf($monitor);

    $notification = new WorkflowNotification($case, WorkflowStatus::Submitted, $monitor);
    $database = $notification->toDatabase($editor);
    $mail = $notification->toMail($editor);

    expect(json_encode($database))->not->toContain('Arta')
        ->and($database['title'])->toContain($case->court_case_number)
        ->and($mail->subject)->toContain($case->court_case_number);
});
