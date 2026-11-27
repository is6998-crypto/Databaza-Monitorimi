<?php

use App\Enums\CaseType;
use App\Enums\PostponementReason;
use App\Enums\Role;
use App\Enums\WorkflowStatus;
use App\Filament\Admin\Pages\PeriodReports;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Documents\Document;
use App\Services\Reports\PeriodReport;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/*
| Period reports for funders: approved data only, small numbers hidden by default, every download logged.
*/

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

/**
 * Approved cases in a court, each with approved hearings on the given dates.
 *
 * @param  list<string>  $dates
 * @param  array<string, mixed>  $hearing
 * @return list<CourtCase>
 */
function reportedCases(int $count, int $courtIndex, array $dates, array $hearing = []): array
{
    return collect(range(1, $count))->map(function () use ($courtIndex, $dates, $hearing): CourtCase {
        $case = CourtCase::factory()->approved()->type(CaseType::CriminalCorruption)->create(['court_id' => basicCourt($courtIndex)->id]);

        foreach ($dates as $date) {
            $row = Hearing::factory()->forCase($case)->make([
                'hearing_date' => $date,
                'was_postponed' => false,
                'started_late' => false,
                'is_public' => true,
                ...$hearing,
            ]);
            $row->status = WorkflowStatus::Approved;
            $row->save();
        }

        return $case;
    })->all();
}

/**
 * Every text of a document, for assertions.
 */
function documentText(Document $document): string
{
    return json_encode([$document->title, $document->subtitle, $document->meta, $document->blocks], JSON_UNESCAPED_UNICODE) ?: '';
}

it('turns the chosen period into dates and a name', function (array $data, string $from, string $until, string $label) {
    $filters = PeriodReports::filters($data);

    expect($filters->from->toDateString())->toBe($from)
        ->and($filters->until->toDateString())->toBe($until)
        ->and(PeriodReport::periodLabel($filters))->toBe($label);
})->with([
    'a quarter' => [['period' => 'quarter', 'year' => 2026, 'quarter' => 3], '2026-07-01', '2026-09-30', 'T3 2026'],
    'a half year' => [['period' => 'half', 'year' => 2026, 'half' => 1], '2026-01-01', '2026-06-30', 'Gjashtëmujori 1 2026'],
    'a year' => [['period' => 'year', 'year' => 2025], '2025-01-01', '2025-12-31', '2025'],
    'other dates' => [['period' => 'custom', 'from' => '2026-02-15', 'until' => '2026-03-20'], '2026-02-15', '2026-03-20', '15.02.2026 – 20.03.2026'],
]);

it('counts only approved data in the period, and hides numbers under 5 unless asked', function () {
    reportedCases(6, 0, ['2026-07-10'], ['was_postponed' => true, 'postponement_reasons' => [PostponementReason::Judge->value]]);
    reportedCases(2, 1, ['2026-08-10']);
    reportedCases(3, 0, ['2026-01-10']);                          // another quarter

    $draft = CourtCase::factory()->create(['court_id' => basicCourt(0)->id]);
    Hearing::factory()->forCase($draft)->create(['hearing_date' => '2026-07-11']);   // not approved

    $filters = new AnalyticsFilters(Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'));

    $hiddenDocument = app(PeriodReport::class)->make($filters, suppress: true);
    $shownDocument = app(PeriodReport::class)->make($filters, suppress: false);
    $hidden = documentText($hiddenDocument);
    $shown = documentText($shownDocument);
    $paragraphs = fn (Document $document): array => collect($document->blocks)->where('type', 'paragraph')->pluck('text')->all();

    // Summary: 8 cases, 8 hearings, 2 courts, 6 of 8 postponed.
    expect($hidden)->toContain('Raporti i monitorimit: T3 2026')
        ->toContain('"Lëndë të monitoruara":"8"')
        ->toContain('"Seanca të monitoruara":"8"')
        ->toContain('75 %')
        // Peja's two cases are under the threshold.
        ->toContain(json_encode([basicCourt(1)->label(), '<5', '<5', '<5'], JSON_UNESCAPED_UNICODE))
        ->toContain(PostponementReason::Judge->getLabel())
        ->and($paragraphs($hiddenDocument))->toContain(__('period_reports.document.suppressed', ['threshold' => 5]));

    expect($shown)->toContain(json_encode([basicCourt(1)->label(), '2', '2', '0,0 %'], JSON_UNESCAPED_UNICODE))
        ->and($paragraphs($shownDocument))->toContain(__('period_reports.document.not_suppressed'));
});

it('is for staff who analyse and export, and logs every download', function (string $format) {
    reportedCases(5, 0, ['2026-07-10']);
    $editor = userWithRole(Role::Editor);
    $this->actingAs($editor);

    Livewire::test(PeriodReports::class)
        ->fillForm(['period' => 'quarter', 'year' => 2026, 'quarter' => 3, 'format' => $format, 'suppress' => true])
        ->assertSee('T3 2026')
        ->call('download')
        ->assertHasNoFormErrors()
        ->assertFileDownloaded('raporti-i-monitorimit-t3-2026.'.$format);

    $log = ExportLog::query()->latest('id')->firstOrFail();

    expect($log->resource)->toBe('period_report')
        ->and($log->format)->toBe($format)
        ->and($log->user_id)->toBe($editor->id)
        ->and($log->filters)->toMatchArray(['from' => '2026-07-01', 'until' => '2026-09-30', 'suppress' => true]);
})->with(['docx', 'pdf']);

it('is not open to monitors', function () {
    $this->actingAs(userWithRole(Role::Analyst))->get(PeriodReports::getUrl())->assertOk();
    $this->actingAs(monitorUser([basicCourt()->id]))->get(PeriodReports::getUrl())->assertForbidden();
});
