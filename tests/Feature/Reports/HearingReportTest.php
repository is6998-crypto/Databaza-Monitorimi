<?php

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Admin\Pages\ReviewQueue;
use App\Filament\Admin\Resources\HearingReports\HearingReportResource;
use App\Filament\Admin\Resources\HearingReports\Pages\CreateHearingReport;
use App\Filament\Admin\Resources\HearingReports\Pages\EditHearingReport;
use App\Filament\Admin\Resources\HearingReports\Pages\ListHearingReports;
use App\Filament\Admin\Resources\HearingReports\Pages\ViewHearingReport;
use App\Filament\Admin\Resources\Hearings\HearingResource;
use App\Filament\Admin\Widgets\Review\ReportsAwaitingReview;
use App\Livewire\Admin\ReportComments;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\User;
use App\Notifications\HearingReportNotification;
use App\Services\HearingReportService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Notification::fake();
    $this->reports = app(HearingReportService::class);
});

/**
 * A hearing monitored (and recorded) by the given monitor.
 */
function hearingOf(User $monitor): Hearing
{
    $case = CourtCase::factory()->create([
        'court_id' => $monitor->monitor->courts()->value('courts.id') ?? basicCourt()->id,
        'court_case_number' => 'PKR.nr.'.fake()->unique()->numberBetween(100, 999).'/2026',
        'created_by' => $monitor->id,
    ]);

    $hearing = Hearing::factory()->forCase($case)->make(['monitor_id' => $monitor->monitor->id]);
    $hearing->created_by = $monitor->id;
    $hearing->save();

    return $hearing->fresh();
}

function reportOn(Hearing $hearing, User $author, ReportStatus $status = ReportStatus::Draft, string $body = '<p>Seanca filloi në kohë. Dëshmitari Agim Sekreti u dëgjua.</p>'): HearingReport
{
    $report = HearingReport::query()->create(['hearing_id' => $hearing->id, 'title' => 'Dëgjohet dëshmitari', 'body' => $body, 'created_by' => $author->id]);
    $report->forceFill(['status' => $status])->save();

    return $report->fresh();
}

it('lets a monitor write the report of a hearing they monitored', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $hearing = hearingOf($monitor);
    $this->actingAs($monitor);

    Livewire::withQueryParams(['hearing' => $hearing->id])
        ->test(CreateHearingReport::class)
        ->assertFormSet(['hearing_id' => $hearing->id])
        ->fillForm(['title' => 'Shtyhet seanca', 'body' => '<p>Seanca u shty sepse mungoi mbrojtësi.</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    $report = HearingReport::query()->where('hearing_id', $hearing->id)->firstOrFail();

    expect($report->status)->toBe(ReportStatus::Draft)
        ->and($report->created_by)->toBe($monitor->id)
        ->and($report->title)->toBe('Shtyhet seanca')
        ->and($report->plainText())->toBe('Seanca u shty sepse mungoi mbrojtësi.')
        ->and($report->wordCount())->toBe(6);
});

it('allows one report per hearing and sends the monitor to the existing one', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $hearing = hearingOf($monitor);
    $report = reportOn($hearing, $monitor);
    $this->actingAs($monitor);

    Livewire::test(CreateHearingReport::class)
        ->fillForm(['hearing_id' => $hearing->id, 'body' => '<p>Një raport i dytë.</p>'])
        ->call('create')
        ->assertHasFormErrors(['hearing_id']);

    // Opened from the hearing, the existing report is shown instead of an empty form.
    Livewire::withQueryParams(['hearing' => $hearing->id])
        ->test(CreateHearingReport::class)
        ->assertRedirect(HearingReportResource::getUrl('view', ['record' => $report]));

    expect(HearingReport::query()->where('hearing_id', $hearing->id)->count())->toBe(1);
});

it('does not let a monitor write about a hearing someone else monitored', function () {
    $court = basicCourt();
    $author = monitorUser([$court->id]);
    $colleague = monitorUser([$court->id]);
    $hearing = hearingOf($author);
    $this->actingAs($colleague);

    // Same court, so the colleague sees the hearing, but the report is not theirs to write.
    expect(HearingReportResource::reportableHearings()->whereKey($hearing->id)->exists())->toBeFalse();

    Livewire::test(CreateHearingReport::class)
        ->fillForm(['hearing_id' => $hearing->id, 'body' => '<p>Tekst.</p>'])
        ->call('create')
        ->assertForbidden();

    expect(HearingReport::query()->count())->toBe(0);
});

it('stores the title, the text and the comments encrypted and keeps them out of the audit log', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = reportOn(hearingOf($monitor), $monitor, ReportStatus::Submitted);

    $this->actingAs($editor);
    $report->update(['body' => '<p>Dëshmitari Agim Sekreti tha se nuk e njeh të akuzuarin.</p>']);
    $this->reports->comment($report, $editor, 'A e tha vërtet këtë Agim Sekreti?');

    $row = DB::table('hearing_reports')->where('id', $report->id)->first();
    $comment = DB::table('hearing_report_comments')->where('hearing_report_id', $report->id)->first();

    expect($row->body)->not->toContain('Sekreti')
        ->and($row->title)->not->toContain('dëshmitari')
        ->and($comment->body)->not->toContain('Sekreti')
        ->and($report->fresh()->body)->toContain('Agim Sekreti');

    $log = Activity::query()->where('log_name', 'reports')->get();

    expect($log->pluck('event')->all())->toContain('created', 'edited', 'commented')
        ->and($log->toJson())->not->toContain('Sekreti')
        ->and($log->firstWhere('event', 'edited')->causer_id)->toBe($editor->id);
});

it('sends the report to the editors, who can edit it, comment and approve it', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $admin = userWithRole(Role::SuperAdmin);
    $analyst = userWithRole(Role::Analyst);
    $report = reportOn(hearingOf($monitor), $monitor);

    $this->reports->submit($report, $monitor);

    expect($report->fresh()->status)->toBe(ReportStatus::Submitted)
        ->and($report->fresh()->submitted_at)->not->toBeNull();
    Notification::assertSentTo([$editor, $admin], HearingReportNotification::class, fn (HearingReportNotification $n): bool => $n->event === HearingReportNotification::SUBMITTED);
    Notification::assertNotSentTo([$monitor, $analyst], HearingReportNotification::class);

    // The editor corrects the text on the edit page.
    $this->actingAs($editor);
    Livewire::test(EditHearingReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(['body' => '<p>Seanca filloi në kohë dhe zgjati dy orë.</p>'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($report->fresh()->plainText())->toBe('Seanca filloi në kohë dhe zgjati dy orë.')
        ->and($report->fresh()->updated_by)->toBe($editor->id);

    $this->reports->comment($report->fresh(), $editor, 'Shtova kohëzgjatjen.');
    Notification::assertSentTo($monitor, HearingReportNotification::class, fn (HearingReportNotification $n): bool => $n->event === HearingReportNotification::COMMENTED);

    $this->reports->approve($report->fresh(), $editor);

    expect($report->fresh()->status)->toBe(ReportStatus::Approved)
        ->and($report->fresh()->reviewed_by)->toBe($editor->id);
    Notification::assertSentTo($monitor, HearingReportNotification::class, fn (HearingReportNotification $n): bool => $n->event === HearingReportNotification::APPROVED);

    // Approved: the monitor can read it but no longer change it; the editor still can.
    expect($monitor->can('view', $report->fresh()))->toBeTrue()
        ->and($monitor->can('update', $report->fresh()))->toBeFalse()
        ->and($editor->can('update', $report->fresh()))->toBeTrue();
});

it('sends a report back only with a comment, and the monitor can answer and send it again', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = reportOn(hearingOf($monitor), $monitor, ReportStatus::Submitted);

    expect(fn () => $this->reports->sendBack($report, $editor, '  '))->toThrow(ValidationException::class);

    $this->reports->sendBack($report, $editor, 'Mungon data e seancës së radhës.');

    expect($report->fresh()->status)->toBe(ReportStatus::Returned)
        ->and($report->fresh()->comments->pluck('body')->all())->toBe(['Mungon data e seancës së radhës.']);
    Notification::assertSentTo($monitor, HearingReportNotification::class, fn (HearingReportNotification $n): bool => $n->event === HearingReportNotification::RETURNED);

    // The monitor answers: the editor who took part is told, not every editor.
    $bystander = userWithRole(Role::Editor);
    $this->reports->comment($report->fresh(), $monitor, 'E shtova.');

    Notification::assertSentTo($editor, HearingReportNotification::class, fn (HearingReportNotification $n): bool => $n->event === HearingReportNotification::COMMENTED);
    Notification::assertNotSentTo($bystander, HearingReportNotification::class);

    $this->reports->submit($report->fresh(), $monitor);
    expect($report->fresh()->status)->toBe(ReportStatus::Submitted);
});

it('never puts the text of a report or a comment into a notification', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = reportOn(hearingOf($monitor), $monitor, ReportStatus::Submitted);

    foreach ([HearingReportNotification::SUBMITTED, HearingReportNotification::RETURNED, HearingReportNotification::APPROVED, HearingReportNotification::COMMENTED] as $event) {
        $notification = new HearingReportNotification($report, $event, $editor);
        $text = json_encode($notification->toDatabase($monitor), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .json_encode($notification->toMail($monitor)->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        expect($text)->not->toContain('Sekreti')
            ->and($text)->not->toContain('Dëgjohet dëshmitari')
            ->and($text)->toContain((string) $report->hearing->case->court_case_number);
    }
});

it('refuses an empty report and the steps a person is not allowed to take', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $stranger = monitorUser([basicCourt(1)->id]);
    $analyst = userWithRole(Role::Analyst);
    $empty = reportOn(hearingOf($monitor), $monitor, ReportStatus::Draft, '<p></p>');
    $report = reportOn(hearingOf($monitor), $monitor, ReportStatus::Submitted);

    expect(fn () => $this->reports->submit($empty, $monitor))->toThrow(ValidationException::class)
        // A monitor cannot approve or send back, not even their own report.
        ->and(fn () => $this->reports->approve($report, $monitor))->toThrow(AuthorizationException::class)
        ->and(fn () => $this->reports->sendBack($report, $monitor, 'x'))->toThrow(AuthorizationException::class)
        // Another monitor has nothing to do with it.
        ->and(fn () => $this->reports->comment($report, $stranger, 'x'))->toThrow(AuthorizationException::class)
        // An analyst reads, and that is all.
        ->and(fn () => $this->reports->comment($report, $analyst, 'x'))->toThrow(AuthorizationException::class)
        ->and($analyst->can('view', $report))->toBeTrue()
        ->and($analyst->can('update', $report))->toBeFalse()
        ->and($analyst->can('create', HearingReport::class))->toBeFalse()
        ->and($stranger->can('view', $report))->toBeFalse()
        ->and($monitor->can('delete', $report))->toBeFalse()
        ->and(userWithRole(Role::Editor)->can('delete', $report))->toBeFalse()
        ->and(userWithRole(Role::SuperAdmin)->can('delete', $report))->toBeTrue()
        ->and(researcher()->can('viewAny', HearingReport::class))->toBeFalse();
});

it('shows a monitor only their own reports and an editor all of them', function () {
    $court = basicCourt();
    $monitor = monitorUser([$court->id]);
    $colleague = monitorUser([$court->id]);
    $mine = reportOn(hearingOf($monitor), $monitor, ReportStatus::Returned);
    $theirs = reportOn(hearingOf($colleague), $colleague, ReportStatus::Submitted);

    $this->actingAs($monitor);

    Livewire::test(ListHearingReports::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);

    // The monitor's badge counts what came back to them; the editor's what waits for review.
    expect(HearingReportResource::getNavigationBadge())->toBe('1');
    $this->get(HearingReportResource::getUrl('view', ['record' => $theirs]))->assertNotFound();

    $this->actingAs(userWithRole(Role::Editor));

    Livewire::test(ListHearingReports::class)->assertCanSeeTableRecords([$mine, $theirs]);
    expect(HearingReportResource::getNavigationBadge())->toBe('1')
        ->and(ReviewQueue::getNavigationBadge())->toBe('1');

    $this->get(ReviewQueue::getUrl())->assertOk();
    Livewire::test(ReportsAwaitingReview::class)
        ->assertCanSeeTableRecords([$theirs])
        ->assertCanNotSeeTableRecords([$mine]);
});

it('renders the report with its comment thread and lets both sides write in it', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = reportOn(hearingOf($monitor), $monitor, ReportStatus::Submitted);
    $this->reports->comment($report, $editor, 'Sa zgjati seanca?');

    $this->actingAs($monitor);

    $this->get(HearingReportResource::getUrl('view', ['record' => $report]))
        ->assertOk()
        ->assertSee('Dëgjohet dëshmitari')
        ->assertSee('Agim Sekreti')
        ->assertSee('Sa zgjati seanca?');

    Livewire::test(ReportComments::class, ['reportId' => $report->id])
        ->assertSee('Sa zgjati seanca?')
        ->set('body', '')
        ->call('post')
        ->assertHasErrors(['body'])
        ->set('body', 'Rreth dy orë.')
        ->call('post')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->assertSee('Rreth dy orë.');

    expect($report->fresh()->comments)->toHaveCount(2);

    // Someone who may not see the report cannot load its thread; an analyst sees it without the form.
    $this->actingAs(monitorUser([basicCourt(1)->id]));
    Livewire::test(ReportComments::class, ['reportId' => $report->id])->assertForbidden();

    $this->actingAs(userWithRole(Role::Analyst));
    Livewire::test(ReportComments::class, ['reportId' => $report->id])
        ->assertSee('Rreth dy orë.')
        ->assertDontSeeHtml('wire:submit="post"')
        ->set('body', 'Nuk duhet të lejohet.')
        ->call('post')
        ->assertForbidden();
});

it('offers the review actions on the report page to the right people', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = reportOn(hearingOf($monitor), $monitor);

    $this->actingAs($monitor);
    Livewire::test(ViewHearingReport::class, ['record' => $report->getRouteKey()])
        ->assertActionVisible('submitReport')
        ->assertActionHidden('approveReport')
        ->assertActionHidden('sendBackReport')
        ->callAction('submitReport');

    expect($report->fresh()->status)->toBe(ReportStatus::Submitted);

    $this->actingAs($editor);
    Livewire::test(ViewHearingReport::class, ['record' => $report->getRouteKey()])
        ->assertActionHidden('submitReport')
        ->assertActionVisible('approveReport')
        ->callAction('sendBackReport', ['comment' => 'Plotësoni arsyen e shtyrjes.']);

    expect($report->fresh()->status)->toBe(ReportStatus::Returned)
        ->and($report->fresh()->comments)->toHaveCount(1);
});

it('links a hearing to its report', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $hearing = hearingOf($monitor);
    $this->actingAs($monitor);

    $this->get(HearingResource::getUrl('view', ['record' => $hearing]))
        ->assertOk()
        ->assertSee('Shkruaj raportin')
        ->assertSee(HearingReportResource::getUrl('create', ['hearing' => $hearing->id]), false);

    $report = reportOn($hearing, $monitor);

    $this->get(HearingResource::getUrl('view', ['record' => $hearing]))
        ->assertOk()
        ->assertSee(HearingReportResource::getUrl('view', ['record' => $report]), false);

    // The monitor app (phone) points to the same page.
    $recent = collect($this->getJson(route('monitor.bootstrap'))->assertOk()->json('recent'))->firstWhere('id', $hearing->id);

    expect($recent['has_report'])->toBeTrue()
        ->and($recent['report_url'])->toBe(HearingReportResource::getUrl('view', ['record' => $report]));
});
