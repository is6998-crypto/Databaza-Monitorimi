<?php

use App\Enums\CasePriority;
use App\Enums\CaseType;
use App\Enums\Role;
use App\Enums\StageType;
use App\Enums\TrialBench;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Pages\Coverage;
use App\Models\CaseStage;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\ScheduledHearing;
use App\Services\Coverage\CoverageChecks;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/*
| Coverage page: the gaps in the monitoring, for editors and analysts.
*/

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Europe/Belgrade'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * A criminal case opened long ago, with a running time limit (indictment filed: initial hearing due).
 *
 * @param  array<string, mixed>  $attributes
 */
function activeCase(array $attributes = []): CourtCase
{
    $case = CourtCase::factory()->type(CaseType::CriminalCorruption)->create(['created_at' => '2026-01-10 09:00:00', 'in_detention' => false, 'trial_bench' => TrialBench::Panel, ...$attributes]);
    CaseStage::query()->create(['case_id' => $case->id, 'stage' => StageType::IndictmentFiled, 'date' => '2026-11-20']);

    return $case->fresh();
}

/**
 * @return list<int>
 */
function ids(Builder $query): array
{
    return $query->pluck($query->getModel()->getQualifiedKeyName())->map(fn ($id): int => (int) $id)->sort()->values()->all();
}

it('is open to editors and analysts, not to monitors', function () {
    $this->actingAs(userWithRole(Role::Editor))->get(Coverage::getUrl())->assertOk();
    $this->actingAs(userWithRole(Role::Analyst))->get(Coverage::getUrl())->assertOk();
    $this->actingAs(monitorUser([basicCourt()->id]))->get(Coverage::getUrl())->assertForbidden();
});

it('finds active cases with no monitored hearing in 60 days', function () {
    $quiet = activeCase();
    Hearing::factory()->forCase($quiet)->create(['hearing_date' => '2026-09-01']);

    $followed = activeCase();
    Hearing::factory()->forCase($followed)->create(['hearing_date' => '2026-11-15']);

    $high = CourtCase::factory()->create(['created_at' => '2026-01-10 09:00:00', 'priority' => CasePriority::High]);
    $inactive = CourtCase::factory()->create(['created_at' => '2026-01-10 09:00:00']);
    $new = activeCase(['created_at' => '2026-11-01 09:00:00']);

    $found = app(CoverageChecks::class)->notFollowed();

    expect(ids($found))->toBe(collect([$quiet->id, $high->id])->sort()->values()->all())
        ->and((clone $found)->whereKey($quiet->id)->first()->getAttribute('last_hearing_on'))->toStartWith('2026-09-01');
});

it('finds recent hearings without a report, missed planned hearings and missing answers', function () {
    $case = activeCase(['in_detention' => null]);
    $noReport = Hearing::factory()->forCase($case)->create(['hearing_date' => '2026-11-20']);
    $tooRecent = Hearing::factory()->forCase($case)->create(['hearing_date' => '2026-11-30']);
    $tooOld = Hearing::factory()->forCase($case)->create(['hearing_date' => '2025-01-20']);
    $withReport = Hearing::factory()->forCase($case)->create(['hearing_date' => '2026-11-19']);
    HearingReport::query()->create(['hearing_id' => $withReport->id, 'title' => 'Raport', 'body' => 'Tekst']);

    $missed = ScheduledHearing::query()->create(['case_id' => $case->id, 'scheduled_on' => '2026-11-25']);
    ScheduledHearing::query()->create(['case_id' => $case->id, 'scheduled_on' => '2026-12-05']);

    $answered = activeCase();
    $checks = app(CoverageChecks::class);

    expect(ids($checks->missingReports()))->toBe([$noReport->id])
        ->and(ids($checks->missedHearings()))->toBe([$missed->id])
        ->and(ids($checks->missingFacts()))->toBe([$case->id])
        ->and(ids($checks->missingFacts()))->not->toContain($answered->id);
});

it('finds drafts nobody touched for two weeks and high-priority cases without a monitor', function () {
    $old = CourtCase::factory()->create(['updated_at' => now()->subDays(20), 'priority' => CasePriority::High]);
    $fresh = CourtCase::factory()->create(['updated_at' => now()->subDays(2)]);
    $returned = Hearing::factory()->forCase($fresh)->make(['updated_at' => now()->subDays(30)]);
    $returned->status = WorkflowStatus::Returned;
    $returned->saveQuietly();
    $approved = CourtCase::factory()->approved()->create(['updated_at' => now()->subDays(40)]);

    ['cases' => $cases, 'hearings' => $hearings] = app(CoverageChecks::class)->staleDrafts();

    expect(ids($cases))->toBe([$old->id])
        ->and(ids($hearings))->toBe([$returned->id])
        ->and(ids($cases))->not->toContain($approved->id)
        ->and(ids(app(CoverageChecks::class)->noMonitor()))->toBe([$old->id]);
});

it('lists every check on the page, with a tile each, and shows all on demand', function () {
    $this->actingAs(userWithRole(Role::Editor));

    foreach (range(1, 10) as $n) {
        CourtCase::factory()->create(['created_at' => '2026-01-10 09:00:00', 'priority' => CasePriority::High, 'court_case_number' => "PKR.nr.{$n}00/2026"]);
    }

    $page = Livewire::test(Coverage::class)->assertOk();

    foreach (CoverageChecks::ALL as $check) {
        $page->assertSeeHtml('data-coverage-section="'.$check.'"')->assertSeeHtml('data-coverage-tile="'.$check.'"');
    }

    $page->assertSee(trans_choice('coverage.show_all', 10, ['count' => 10]))
        ->call('toggle', CoverageChecks::NOT_FOLLOWED)
        ->assertSee(__('coverage.show_less'));

    foreach (range(1, 10) as $n) {
        $page->assertSee("PKR.nr.{$n}00/2026");
    }
});
