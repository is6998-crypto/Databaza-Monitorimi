<?php

use App\Enums\CaseType;
use App\Enums\DeadlineRule;
use App\Enums\DeadlineState;
use App\Enums\Role;
use App\Enums\StageOutcome;
use App\Enums\StageType;
use App\Enums\TrialBench;
use App\Filament\Admin\Resources\Cases\CourtCaseResource;
use App\Filament\Admin\Resources\Cases\Pages\ViewCourtCase;
use App\Filament\Admin\Resources\Cases\RelationManagers\DeadlinesRelationManager;
use App\Filament\Admin\Resources\Deadlines\CaseDeadlineResource;
use App\Filament\Admin\Resources\Deadlines\Pages\ListCaseDeadlines;
use App\Models\CaseDeadline;
use App\Models\CaseParty;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Notifications\DeadlinesExceeded;
use App\Services\Deadlines\DeadlineNotifier;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    // "Today" is fixed so that the limits are exact: 1 September 2026 in Kosovo.
    Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00', 'Europe/Belgrade'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function criminalCase(array $attributes = []): CourtCase
{
    return CourtCase::factory()->type(CaseType::CriminalCorruption)->create($attributes);
}

function stage(CourtCase $case, StageType $stage, string $date, ?StageOutcome $outcome = null): CaseStage
{
    return CaseStage::query()->create(['case_id' => $case->id, 'stage' => $stage, 'date' => $date, 'outcome' => $outcome]);
}

function deadline(CourtCase $case, DeadlineRule $rule): ?CaseDeadline
{
    return CaseDeadline::query()->where('case_id', $case->id)->where('rule', $rule->value)->first();
}

it('applies the limit the Criminal Procedure Code gives for each phase', function (DeadlineRule $rule, StageType $start, array $facts, string $due, string $basis) {
    $case = criminalCase($facts);
    stage($case, $start, '2026-01-10');

    $row = deadline($case, $rule);

    expect($row)->not->toBeNull()
        ->and($row->started_on->toDateString())->toBe('2026-01-10')
        ->and($row->due_on->toDateString())->toBe($due)
        ->and($row->basis)->toBe($basis)
        ->and($row->state)->toBe(DeadlineState::Open);
})->with([
    'investigation: 2 years (Art. 157)' => [DeadlineRule::Investigation, StageType::InvestigationOpened, [], '2028-01-10', 'standard'],
    'investigation extended: 3 years' => [DeadlineRule::Investigation, StageType::InvestigationOpened, ['investigation_extended' => true], '2029-01-10', 'extended'],
    'indictment: 3 months after the investigation (Art. 234)' => [DeadlineRule::IndictmentFiling, StageType::InvestigationClosed, [], '2026-04-10', 'standard'],
    'initial hearing: 30 days (Art. 236)' => [DeadlineRule::InitialHearing, StageType::IndictmentFiled, [], '2026-02-09', 'standard'],
    'initial hearing in detention: 15 days' => [DeadlineRule::InitialHearing, StageType::IndictmentFiled, ['in_detention' => true], '2026-01-25', 'detention'],
    'main trial scheduled: 30 days (Art. 249)' => [DeadlineRule::MainTrialScheduling, StageType::IndictmentDecision, [], '2026-02-09', 'standard'],
    'main trial, single judge: 90 days (Art. 310)' => [DeadlineRule::MainTrial, StageType::MainHearing, ['trial_bench' => TrialBench::SingleJudge], '2026-04-10', 'single_judge'],
    'main trial, panel: 120 days' => [DeadlineRule::MainTrial, StageType::MainHearing, ['trial_bench' => TrialBench::Panel], '2026-05-10', 'panel'],
    'main trial, bench not recorded: the longer limit' => [DeadlineRule::MainTrial, StageType::MainHearing, [], '2026-05-10', 'bench_unknown'],
    'announcement: 3 days after the trial ends (Art. 365)' => [DeadlineRule::JudgmentAnnouncement, StageType::MainHearingConcluded, [], '2026-01-13', 'standard'],
    'written judgment: 30 days (Art. 368)' => [DeadlineRule::JudgmentDrafting, StageType::JudgmentAnnounced, [], '2026-02-09', 'standard'],
    'written judgment in detention: 15 days' => [DeadlineRule::JudgmentDrafting, StageType::JudgmentAnnounced, ['in_detention' => true], '2026-01-25', 'detention'],
    'written judgment, complex case: 60 days' => [DeadlineRule::JudgmentDrafting, StageType::JudgmentAnnounced, ['is_complex' => true], '2026-03-11', 'extended'],
    'appeal in detention: 3 months from the file (Art. 405)' => [DeadlineRule::AppealDecision, StageType::AppealFilesReceived, ['in_detention' => true], '2026-04-10', 'detention'],
]);

it('keeps the longest limit the law allows next to the regular one', function () {
    $case = criminalCase();
    stage($case, StageType::JudgmentAnnounced, '2026-01-10');
    stage($case, StageType::InvestigationOpened, '2023-01-10');

    expect(deadline($case, DeadlineRule::JudgmentDrafting)->extended_due_on->toDateString())->toBe('2026-03-11')
        ->and(deadline($case, DeadlineRule::Investigation)->due_on->toDateString())->toBe('2025-01-10')
        ->and(deadline($case, DeadlineRule::Investigation)->extended_due_on->toDateString())->toBe('2026-01-10');

    // Once the case is marked complex, 60 days is the limit itself.
    $case->update(['is_complex' => true]);

    expect(deadline($case, DeadlineRule::JudgmentDrafting)->due_on->toDateString())->toBe('2026-03-11')
        ->and(deadline($case, DeadlineRule::JudgmentDrafting)->extended_due_on)->toBeNull();
});

it('has no limit for the appeal unless the accused is detained, and none for civil cases', function () {
    $case = criminalCase();
    stage($case, StageType::AppealFiled, '2026-01-10');

    expect(deadline($case, DeadlineRule::AppealDecision))->toBeNull();

    $case->update(['in_detention' => true]);

    // Counted from the day of the appeal while the day the file reached the Court of Appeals is unknown.
    expect(deadline($case, DeadlineRule::AppealDecision)->start_stage)->toBe(StageType::AppealFiled);

    stage($case, StageType::AppealFilesReceived, '2026-02-20');

    expect(deadline($case, DeadlineRule::AppealDecision)->start_stage)->toBe(StageType::AppealFilesReceived)
        ->and(deadline($case, DeadlineRule::AppealDecision)->due_on->toDateString())->toBe('2026-05-20');

    $civil = CourtCase::factory()->type(CaseType::Civil)->create();
    stage($civil, StageType::JudgmentAnnounced, '2026-01-10');

    expect(CaseDeadline::query()->where('case_id', $civil->id)->exists())->toBeFalse();
});

it('schedules no main trial for a rejected indictment or an accepted guilty plea', function () {
    $rejected = criminalCase();
    stage($rejected, StageType::IndictmentDecision, '2026-01-10', StageOutcome::Rejected);

    $plea = criminalCase();
    stage($plea, StageType::IndictmentDecision, '2026-01-10', StageOutcome::Approved);
    stage($plea, StageType::PleaAgreement, '2026-01-12', StageOutcome::Accepted);

    $confirmed = criminalCase();
    stage($confirmed, StageType::IndictmentDecision, '2026-01-10', StageOutcome::Approved);

    expect(deadline($rejected, DeadlineRule::MainTrialScheduling))->toBeNull()
        ->and(deadline($plea, DeadlineRule::MainTrialScheduling))->toBeNull()
        ->and(deadline($confirmed, DeadlineRule::MainTrialScheduling))->not->toBeNull();
});

it('follows the stages: met, late, closed without a date, and suspended', function () {
    $case = criminalCase();
    stage($case, StageType::JudgmentAnnounced, '2026-01-10');

    $drafted = stage($case, StageType::JudgmentDrafted, '2026-02-01');
    expect(deadline($case, DeadlineRule::JudgmentDrafting)->state)->toBe(DeadlineState::Met);

    $drafted->update(['date' => '2026-03-01']);
    $row = deadline($case, DeadlineRule::JudgmentDrafting);
    expect($row->state)->toBe(DeadlineState::Late)
        ->and($row->daysOver())->toBe(20)
        ->and($row->status())->toBe('late');

    // The end date is removed, but an appeal exists: the phase is over, only its date is missing.
    $drafted->delete();
    stage($case, StageType::AppealFiled, '2026-03-20');
    expect(deadline($case, DeadlineRule::JudgmentDrafting)->state)->toBe(DeadlineState::Closed);

    $suspended = criminalCase();
    stage($suspended, StageType::InvestigationOpened, '2020-01-10');
    stage($suspended, StageType::InvestigationSuspended, '2020-06-01');
    expect(deadline($suspended, DeadlineRule::Investigation)->state)->toBe(DeadlineState::Paused);

    // Removing the start removes the limit.
    $only = criminalCase();
    $start = stage($only, StageType::IndictmentFiled, '2026-01-10');
    expect(deadline($only, DeadlineRule::InitialHearing))->not->toBeNull();
    $start->delete();
    expect(deadline($only, DeadlineRule::InitialHearing))->toBeNull();
});

it('tells overdue from due soon from running by today\'s date', function () {
    $overdue = criminalCase();
    stage($overdue, StageType::JudgmentAnnounced, '2026-06-01');   // due 1 July, 62 days ago

    $soon = criminalCase();
    stage($soon, StageType::JudgmentAnnounced, '2026-08-05');      // due 4 September, in 3 days

    $lastDay = criminalCase();
    stage($lastDay, StageType::JudgmentAnnounced, '2026-08-02');   // due today

    $running = criminalCase();
    stage($running, StageType::JudgmentAnnounced, '2026-08-25');   // due 24 September

    expect(deadline($overdue, DeadlineRule::JudgmentDrafting)->status())->toBe('overdue')
        ->and(deadline($overdue, DeadlineRule::JudgmentDrafting)->daysOver())->toBe(62)
        ->and(deadline($overdue, DeadlineRule::JudgmentDrafting)->isPastExtension())->toBeTrue()
        ->and(deadline($soon, DeadlineRule::JudgmentDrafting)->status())->toBe('due_soon')
        ->and(deadline($soon, DeadlineRule::JudgmentDrafting)->daysLeft())->toBe(3)
        ->and(deadline($lastDay, DeadlineRule::JudgmentDrafting)->status())->toBe('due_soon')
        ->and(deadline($lastDay, DeadlineRule::JudgmentDrafting)->daysLeft())->toBe(0)
        ->and(deadline($running, DeadlineRule::JudgmentDrafting)->status())->toBe('running')
        ->and(CaseDeadline::query()->overdue()->pluck('case_id')->all())->toBe([$overdue->id])
        ->and(CaseDeadline::query()->dueSoon()->pluck('case_id')->all())->toEqualCanonicalizing([$soon->id, $lastDay->id])
        ->and(CaseDeadline::query()->running()->pluck('case_id')->all())->toBe([$running->id]);
});

it('notifies the monitor, the editors and the administrators once when a limit is exceeded', function () {
    Notification::fake();

    $court = basicCourt();
    $monitor = monitorUser([$court->id]);
    $otherMonitor = monitorUser([basicCourt(1)->id]);
    $editor = userWithRole(Role::Editor);
    $admin = userWithRole(Role::SuperAdmin);
    $analyst = userWithRole(Role::Analyst);
    $inactiveEditor = userWithRole(Role::Editor, attributes: ['is_active' => false]);
    $outsider = researcher();

    $case = criminalCase(['court_id' => $court->id, 'assigned_monitor_id' => $monitor->monitor->id, 'created_by' => $monitor->id]);
    CaseParty::factory()->create(['case_id' => $case->id, 'name' => 'Zenel Xhafolli']);
    stage($case, StageType::JudgmentAnnounced, '2026-07-20');   // due 19 August: 13 days over, the 60 days not yet

    $result = app(DeadlineNotifier::class)->notify();

    expect($result)->toBe(['deadlines' => 1, 'recipients' => 3]);

    foreach ([$monitor, $editor, $admin] as $user) {
        Notification::assertSentTo($user, DeadlinesExceeded::class, function (DeadlinesExceeded $notification) use ($user, $case): bool {
            $message = $notification->toDatabase($user);
            $text = json_encode($message, JSON_UNESCAPED_UNICODE).' '.implode(' ', $notification->lines('sq')).' '.$notification->title('sq');

            return str_contains($notification->title('sq'), 'Përpilimi i aktgjykimit')
                && str_contains($text, (string) $case->court_case_number)
                && str_contains($text, '13 ditë')
                && str_contains($text, '368')
                && ! str_contains($text, 'Xhafolli')
                && str_contains($notification->url(), '/admin/cases/'.$case->id);
        });
    }

    Notification::assertNotSentTo([$otherMonitor, $analyst, $inactiveEditor, $outsider], DeadlinesExceeded::class);

    // The next morning nothing is repeated.
    expect(app(DeadlineNotifier::class)->notify())->toBe(['deadlines' => 0, 'recipients' => 0]);
    Notification::assertSentToTimes($editor, DeadlinesExceeded::class, 1);

    // When the longest limit (60 days) passes too, they hear about it once more.
    Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'Europe/Belgrade'));
    expect(app(DeadlineNotifier::class)->notify()['deadlines'])->toBe(1);
    Notification::assertSentToTimes($editor, DeadlinesExceeded::class, 2);
    expect(app(DeadlineNotifier::class)->notify()['deadlines'])->toBe(0);
});

it('does not notify about phases that finished, even late, or about deleted cases', function () {
    Notification::fake();
    userWithRole(Role::Editor);

    $late = criminalCase();
    stage($late, StageType::JudgmentAnnounced, '2026-01-10');
    stage($late, StageType::JudgmentDrafted, '2026-05-01');

    $deleted = criminalCase();
    stage($deleted, StageType::JudgmentAnnounced, '2026-01-10');
    $deleted->delete();

    expect(app(DeadlineNotifier::class)->notify())->toBe(['deadlines' => 0, 'recipients' => 0]);
    Notification::assertNothingSent();
});

it('sends one summary when many limits are exceeded at once', function () {
    Notification::fake();
    $editor = userWithRole(Role::Editor);

    foreach (range(1, 8) as $n) {
        stage(criminalCase(), StageType::IndictmentFiled, '2026-03-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT));
    }

    expect(app(DeadlineNotifier::class)->notify())->toBe(['deadlines' => 8, 'recipients' => 1]);

    Notification::assertSentToTimes($editor, DeadlinesExceeded::class, 1);
    Notification::assertSentTo($editor, DeadlinesExceeded::class, function (DeadlinesExceeded $notification): bool {
        $lines = $notification->lines('sq');

        // Five are named, the other three are counted; the link opens the list.
        return str_contains($notification->title('sq'), '8 afate ligjore')
            && count($lines) === 6
            && str_contains(end($lines), '3')
            && str_ends_with($notification->url(), '/admin/legal-deadlines');
    });
});

it('notifies again when a corrected date moves the limit', function () {
    Notification::fake();
    $editor = userWithRole(Role::Editor);

    $case = criminalCase();
    $announced = stage($case, StageType::JudgmentAnnounced, '2026-07-20');
    app(DeadlineNotifier::class)->notify();

    expect(deadline($case, DeadlineRule::JudgmentDrafting)->notified_at)->not->toBeNull();

    // The date was wrong: the judgment was announced a week later.
    $announced->update(['date' => '2026-07-27']);
    expect(deadline($case, DeadlineRule::JudgmentDrafting)->notified_at)->toBeNull();

    app(DeadlineNotifier::class)->notify();
    Notification::assertSentToTimes($editor, DeadlinesExceeded::class, 2);
});

it('recomputes every case and notifies from the scheduled command', function () {
    Notification::fake();
    $editor = userWithRole(Role::Editor);

    $case = criminalCase();
    stage($case, StageType::JudgmentAnnounced, '2026-07-20');

    // As after an import that wrote the rows without model events.
    CaseDeadline::query()->delete();

    $this->artisan('deadlines:check', ['--no-notify' => true])->assertSuccessful();
    expect(deadline($case, DeadlineRule::JudgmentDrafting))->not->toBeNull();
    Notification::assertNothingSent();

    $this->artisan('deadlines:check')->assertSuccessful();
    Notification::assertSentTo($editor, DeadlinesExceeded::class);
});

it('lists the exceeded limits, and a monitor sees only the courts assigned to them', function () {
    $court = basicCourt();
    $other = basicCourt(1);
    $monitor = monitorUser([$court->id]);

    $mine = criminalCase(['court_id' => $court->id, 'court_case_number' => 'PKR.nr.1/2026']);
    stage($mine, StageType::JudgmentAnnounced, '2026-06-01');

    $theirs = criminalCase(['court_id' => $other->id, 'court_case_number' => 'PKR.nr.2/2026']);
    stage($theirs, StageType::JudgmentAnnounced, '2026-06-01');

    $running = criminalCase(['court_id' => $court->id, 'court_case_number' => 'PKR.nr.3/2026']);
    stage($running, StageType::JudgmentAnnounced, '2026-08-25');

    $this->actingAs($monitor);

    Livewire::test(ListCaseDeadlines::class)
        ->assertCanSeeTableRecords([deadline($mine, DeadlineRule::JudgmentDrafting)])
        ->assertCanNotSeeTableRecords([deadline($theirs, DeadlineRule::JudgmentDrafting), deadline($running, DeadlineRule::JudgmentDrafting)])
        ->set('activeTab', 'running')
        ->assertCanSeeTableRecords([deadline($running, DeadlineRule::JudgmentDrafting)]);

    expect(CaseDeadlineResource::getNavigationBadge())->toBe('1');

    $this->actingAs(userWithRole(Role::Editor));
    expect(CaseDeadlineResource::getNavigationBadge())->toBe('2');

    $this->get(CaseDeadlineResource::getUrl('index'))->assertOk()->assertSee('PKR.nr.1/2026')->assertSee('PKR.nr.2/2026');
});

it('shows the limits inside the case and nobody can edit them', function () {
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);

    $case = criminalCase();
    stage($case, StageType::JudgmentAnnounced, '2026-06-01');
    $row = deadline($case, DeadlineRule::JudgmentDrafting);

    Livewire::test(DeadlinesRelationManager::class, ['ownerRecord' => $case, 'pageClass' => ViewCourtCase::class])
        ->assertCanSeeTableRecords([$row])
        ->assertSee('Tejkaluar');

    $this->get(CourtCaseResource::getUrl('view', ['record' => $case]))
        ->assertOk()
        // On the rail of the case, under the step whose limit passed.
        ->assertSee('Rrjedha e lëndës')
        ->assertSee('Tejkaluar +62 ditë');

    foreach ([$editor, userWithRole(Role::SuperAdmin)] as $user) {
        // Super administrators pass every gate; the rows are still rebuilt from the stages.
        expect($user->can('viewAny', CaseDeadline::class))->toBeTrue();
    }

    expect($editor->can('update', $row))->toBeFalse()
        ->and($editor->can('delete', $row))->toBeFalse()
        ->and($editor->can('create', CaseDeadline::class))->toBeFalse()
        ->and(researcher()->can('viewAny', CaseDeadline::class))->toBeFalse()
        ->and(DeadlinesRelationManager::canViewForRecord(CourtCase::factory()->type(CaseType::Civil)->create(), ViewCourtCase::class))->toBeFalse();
});

it('reads the limits from the configuration', function () {
    config()->set('deadlines.rules.judgment_drafting.standard', [45, 'days']);

    $case = criminalCase();
    stage($case, StageType::JudgmentAnnounced, '2026-01-10');

    expect(deadline($case, DeadlineRule::JudgmentDrafting)->due_on->toDateString())->toBe('2026-02-24')
        ->and(deadline($case, DeadlineRule::JudgmentDrafting)->limitLabel('sq'))->toBe('45 ditë')
        ->and(DeadlineRule::JudgmentDrafting->article())->toBe('368');
});
