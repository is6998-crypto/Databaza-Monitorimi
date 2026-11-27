<?php

use App\Enums\CasePriority;
use App\Enums\Role;
use App\Enums\ScheduledHearingStatus;
use App\Filament\Admin\Resources\Hearings\Pages\CreateHearing;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\CreateScheduledHearing;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\EditScheduledHearing;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\HearingCalendar;
use App\Filament\Admin\Resources\ScheduledHearings\Pages\ListScheduledHearings;
use App\Filament\Admin\Widgets\UpcomingHearingsWidget;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\ScheduledHearing;
use App\Notifications\HearingReminder;
use App\Notifications\UnstaffedHearings;
use App\Services\Calendar\CalendarNotifier;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

/*
| The hearing calendar: planned court sessions, who attends them, and whether they were monitored.
| Staff planning data only.
*/

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    // "Today" is Tuesday 1 December 2026 in Kosovo.
    Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Europe/Belgrade'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function planned(CourtCase $case, string $date, array $attributes = []): ScheduledHearing
{
    return ScheduledHearing::query()->create(['case_id' => $case->id, 'scheduled_on' => $date, ...$attributes]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function monitoredHearing(CourtCase $case, string $date, array $attributes = []): Hearing
{
    return Hearing::factory()->forCase($case)->create(['hearing_date' => $date, ...$attributes]);
}

it('adds the next hearing noted at a hearing to the calendar, for the monitor of the case', function () {
    $court = basicCourt();
    $monitor = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id, 'assigned_monitor_id' => $monitor->monitor->id]);

    $hearing = monitoredHearing($case, '2026-11-30', ['next_hearing_on' => '2027-01-12', 'next_hearing_time' => '10:30']);

    $scheduled = ScheduledHearing::query()->where('source_hearing_id', $hearing->id)->sole();

    expect($scheduled->case_id)->toBe($case->id)
        ->and($scheduled->scheduled_on->toDateString())->toBe('2027-01-12')
        ->and($scheduled->scheduled_time)->toBe('10:30')
        ->and($scheduled->monitor_id)->toBe($monitor->monitor->id)
        ->and($scheduled->status())->toBe(ScheduledHearingStatus::Upcoming);

    // A corrected date moves it; clearing the date takes it off the calendar.
    $hearing->update(['next_hearing_on' => '2027-01-19']);
    expect($scheduled->fresh()->scheduled_on->toDateString())->toBe('2027-01-19');

    $hearing->update(['next_hearing_on' => null]);
    expect(ScheduledHearing::query()->where('source_hearing_id', $hearing->id)->exists())->toBeFalse()
        ->and($hearing->fresh()->next_hearing_time)->toBeNull();
});

it('uses a hearing already entered by hand for that case and day instead of adding a second one', function () {
    $case = CourtCase::factory()->create();
    $byHand = planned($case, '2027-01-12', ['courtroom' => '7']);

    $hearing = monitoredHearing($case, '2026-11-30', ['next_hearing_on' => '2027-01-12', 'next_hearing_time' => '09:00']);

    expect(ScheduledHearing::query()->where('case_id', $case->id)->count())->toBe(1)
        ->and($byHand->fresh()->source_hearing_id)->toBe($hearing->id)
        ->and($byHand->fresh()->scheduled_time)->toBe('09:00')
        ->and($byHand->fresh()->courtroom)->toBe('7');
});

it('marks a planned hearing as monitored when the hearing of that day is recorded, and undoes it', function () {
    $case = CourtCase::factory()->create();
    $scheduled = planned($case, '2026-11-30');

    expect($scheduled->status())->toBe(ScheduledHearingStatus::Missed);

    $hearing = monitoredHearing($case, '2026-11-30');
    expect($scheduled->fresh()->hearing_id)->toBe($hearing->id)
        ->and($scheduled->fresh()->status())->toBe(ScheduledHearingStatus::Attended);

    // The date was wrong: no longer that planned hearing.
    $hearing->update(['hearing_date' => '2026-11-27']);
    expect($scheduled->fresh()->hearing_id)->toBeNull();

    $hearing->update(['hearing_date' => '2026-11-30']);
    $hearing->delete();
    expect($scheduled->fresh()->hearing_id)->toBeNull();
});

it('takes the next hearing from the monitor app and lists the coming hearings there', function () {
    [$mine, $other] = [basicCourt(0), basicCourt(1)];
    $user = monitorUser([$mine->id]);
    $case = CourtCase::factory()->create(['court_id' => $mine->id, 'assigned_monitor_id' => $user->monitor->id]);

    $nobody = planned(CourtCase::factory()->create(['court_id' => $mine->id]), '2026-12-01');
    $colleague = planned(CourtCase::factory()->create(['court_id' => $mine->id]), '2026-12-02', ['monitor_id' => monitorUser([basicCourt(0)->id])->monitor->id]);
    $elsewhere = planned(CourtCase::factory()->create(['court_id' => $other->id]), '2026-12-02');
    $later = planned(CourtCase::factory()->create(['court_id' => $mine->id]), '2027-02-01');

    // Recorded in the app (the cases above were entered by others, so only the court decides what this monitor sees).
    $this->actingAs($user)->postJson('/m/api/hearings', [
        ...appHearing($case, '2026-11-30'),
        'next_hearing_on' => '2026-12-03',
        'next_hearing_time' => '11:00',
    ])->assertCreated();

    $upcoming = collect($this->getJson('/m/api/bootstrap')->assertOk()->json('upcoming'));

    expect($upcoming->pluck('id')->all())->toBe([$nobody->id, ScheduledHearing::query()->where('case_id', $case->id)->value('id')])
        ->and($upcoming->first()['today'])->toBeTrue()
        ->and($upcoming->first()['mine'])->toBeFalse()
        ->and($upcoming->last()['time'])->toBe('11:00')
        ->and($upcoming->last()['mine'])->toBeTrue()
        ->and($upcoming->pluck('id'))->not->toContain($colleague->id)->not->toContain($elsewhere->id)->not->toContain($later->id);
});

it('refuses a next hearing that is not after the hearing', function () {
    $court = basicCourt();
    $user = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);

    $this->actingAs($user)->postJson('/m/api/hearings', [...appHearing($case, '2026-11-30'), 'next_hearing_on' => '2026-11-30'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('next_hearing_on');
});

it('lets monitors plan hearings in their courts and take one nobody attends, but not assign others', function () {
    [$mine, $other] = [basicCourt(0), basicCourt(1)];
    $user = monitorUser([$mine->id]);
    $case = CourtCase::factory()->create(['court_id' => $mine->id]);
    $foreign = planned(CourtCase::factory()->create(['court_id' => $other->id]), '2026-12-10');
    $this->actingAs($user);

    Livewire::test(CreateScheduledHearing::class)
        ->assertFormFieldDisabled('monitor_id')
        ->fillForm(['case_id' => $case->id, 'scheduled_on' => '2026-12-10', 'scheduled_time' => '09:30', 'courtroom' => '3'])
        ->call('create')
        ->assertHasNoFormErrors();

    $scheduled = ScheduledHearing::query()->where('case_id', $case->id)->sole();

    // The case has no monitor, so the one who entered it attends.
    expect($scheduled->monitor_id)->toBe($user->monitor->id)
        ->and($user->can('view', $foreign))->toBeFalse()
        ->and($user->can('delete', $scheduled))->toBeFalse();

    $open = planned($case, '2026-12-11');
    Livewire::test(ListScheduledHearings::class)
        ->assertCanSeeTableRecords([$scheduled, $open])
        ->assertCanNotSeeTableRecords([$foreign])
        ->callTableAction('attend', $open)
        ->assertHasNoTableActionErrors();

    expect($open->fresh()->monitor_id)->toBe($user->monitor->id)
        ->and($user->can('attend', $open->fresh()))->toBeFalse();
});

it('lets editors choose who attends and analysts only read', function () {
    $editor = userWithRole(Role::Editor);
    $monitor = monitorUser([basicCourt()->id]);
    $case = CourtCase::factory()->create(['court_id' => basicCourt()->id]);
    $scheduled = planned($case, '2026-12-10');

    $this->actingAs($editor);
    Livewire::test(EditScheduledHearing::class, ['record' => $scheduled->getRouteKey()])
        ->fillForm(['monitor_id' => $monitor->monitor->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($scheduled->fresh()->monitor_id)->toBe($monitor->monitor->id);

    $analyst = userWithRole(Role::Analyst);
    expect($analyst->can('viewAny', ScheduledHearing::class))->toBeTrue()
        ->and($analyst->can('view', $scheduled))->toBeTrue()
        ->and($analyst->can('create', ScheduledHearing::class))->toBeFalse()
        ->and($analyst->can('update', $scheduled))->toBeFalse()
        ->and($editor->can('delete', $scheduled))->toBeFalse()
        ->and(userWithRole(Role::SuperAdmin)->can('delete', $scheduled))->toBeTrue();
});

it('cancels a hearing and keeps it out of the coming ones', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $scheduled = planned(CourtCase::factory()->create(), '2026-12-10');

    Livewire::test(EditScheduledHearing::class, ['record' => $scheduled->getRouteKey()])
        ->callAction('cancel', data: ['note' => 'Gjykata e shtyu.'])
        ->assertHasNoActionErrors();

    expect($scheduled->fresh()->status())->toBe(ScheduledHearingStatus::Cancelled)
        ->and(ScheduledHearing::query()->upcoming()->exists())->toBeFalse();
});

it('shows the month with its hearings, and filters them', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $high = CourtCase::factory()->create(['court_id' => basicCourt(0)->id, 'priority' => CasePriority::High, 'court_case_number' => 'PKR.nr.11/2026']);
    $normal = CourtCase::factory()->create(['court_id' => basicCourt(1)->id, 'court_case_number' => 'PKR.nr.22/2026']);
    planned($high, '2026-12-08', ['scheduled_time' => '09:00']);
    planned($normal, '2026-12-15', ['monitor_id' => monitorUser([basicCourt(1)->id])->monitor->id]);
    planned($normal, '2027-01-05');

    Livewire::test(HearingCalendar::class)
        ->assertSee('Dhjetor 2026')
        ->assertSee('PKR.nr.11/2026')
        ->assertSee('PKR.nr.22/2026')
        ->assertSeeHtml('data-day="2026-12-08"')
        ->assertSee(trans_choice('calendar.summary_unstaffed', 1, ['count' => 1]))
        ->set('court', (string) basicCourt(0)->id)
        ->assertSee('PKR.nr.11/2026')
        ->assertDontSee('PKR.nr.22/2026')
        ->set('court', null)
        ->call('nextMonth')
        ->assertSet('month', '2027-01')
        ->assertSee('Janar 2027');
})->skip(fn () => app()->getLocale() !== 'sq', 'month names are checked in Albanian');

it('lists missed hearings and opens "add hearing" on the planned day', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $case = CourtCase::factory()->create();
    $missed = planned($case, '2026-11-26');
    $coming = planned($case, '2026-12-20');

    Livewire::test(ListScheduledHearings::class)
        ->assertCanSeeTableRecords([$coming])
        ->assertCanNotSeeTableRecords([$missed])
        ->set('activeTab', 'missed')
        ->assertCanSeeTableRecords([$missed])
        ->assertCanNotSeeTableRecords([$coming])
        ->assertTableActionVisible('recordHearing', $missed);

    Livewire::withQueryParams(['case' => $case->id, 'date' => '2026-11-26'])
        ->test(CreateHearing::class)
        ->assertSchemaStateSet(['case_id' => $case->id, 'hearing_date' => '2026-11-26']);
});

it('reminds the monitor who attends a hearing the day before, once', function () {
    Notification::fake();
    $court = basicCourt();
    $monitor = monitorUser([$court->id]);
    $case = CourtCase::factory()->create(['court_id' => $court->id]);
    $tomorrow = planned($case, '2026-12-02', ['scheduled_time' => '09:30', 'courtroom' => '4', 'monitor_id' => $monitor->monitor->id, 'note' => 'Dëshmitari Arben Testaj']);
    planned($case, '2026-12-03', ['monitor_id' => $monitor->monitor->id]);
    planned(CourtCase::factory()->create(['court_id' => $court->id]), '2026-12-02');

    expect(app(CalendarNotifier::class)->remind())->toBe(['hearings' => 1, 'recipients' => 1]);

    Notification::assertSentTo($monitor, HearingReminder::class, function (HearingReminder $notification) use ($case): bool {
        $text = $notification->title('sq').' '.implode(' ', $notification->lines('sq'));

        return str_contains($text, 'Nesër keni një seancë')
            && str_contains($text, '09:30')
            && str_contains($text, (string) $case->court_case_number)
            && str_contains($text, 'Salla 4')
            && ! str_contains($text, 'Testaj');
    });

    expect(app(CalendarNotifier::class)->remind()['hearings'])->toBe(0);

    // Moved to another day and back: reminded again.
    $tomorrow->update(['scheduled_on' => '2026-12-04']);
    $tomorrow->update(['scheduled_on' => '2026-12-02']);
    expect(app(CalendarNotifier::class)->remind()['hearings'])->toBe(1);
});

it('tells editors about high-priority hearings this week that nobody attends', function () {
    Notification::fake();
    $editor = userWithRole(Role::Editor);
    $admin = userWithRole(Role::SuperAdmin);
    $monitor = monitorUser([basicCourt()->id]);
    $high = CourtCase::factory()->create(['priority' => CasePriority::High]);

    $unstaffed = planned($high, '2026-12-04');
    planned($high, '2026-12-05', ['monitor_id' => $monitor->monitor->id]);
    planned($high, '2026-12-20');
    planned(CourtCase::factory()->create(), '2026-12-04');

    expect(app(CalendarNotifier::class)->warnUnstaffed())->toBe(['hearings' => 1, 'recipients' => 2]);

    Notification::assertSentTo([$editor, $admin], UnstaffedHearings::class, fn (UnstaffedHearings $notification): bool => $notification->ids === [$unstaffed->id]);
    Notification::assertNotSentTo($monitor, UnstaffedHearings::class);

    expect(app(CalendarNotifier::class)->warnUnstaffed()['hearings'])->toBe(0);

    $this->artisan('hearings:remind')->assertSuccessful();
});

it('shows the coming week on the dashboard, unattended high-priority hearings first', function () {
    $this->actingAs(userWithRole(Role::Editor));
    $normal = planned(CourtCase::factory()->create(['court_case_number' => 'PKR.nr.1/2026']), '2026-12-01', ['monitor_id' => monitorUser([basicCourt()->id])->monitor->id]);
    $urgent = planned(CourtCase::factory()->create(['court_case_number' => 'PKR.nr.2/2026', 'priority' => CasePriority::High]), '2026-12-07');
    planned(CourtCase::factory()->create(['court_case_number' => 'PKR.nr.3/2026']), '2026-12-30');

    Livewire::test(UpcomingHearingsWidget::class)
        ->assertSeeInOrder(['PKR.nr.2/2026', 'PKR.nr.1/2026'])
        ->assertDontSee('PKR.nr.3/2026');
});

/**
 * @return array<string, mixed>
 */
function appHearing(CourtCase $case, string $date): array
{
    return [
        'client_uuid' => (string) Str::uuid(),
        'case_id' => $case->id,
        'hearing_date' => $date,
        'started_late' => false,
        'was_postponed' => true,
        'postponement_reasons' => ['judge'],
        'interpretation_needed' => false,
        'phone_used' => false,
    ];
}
