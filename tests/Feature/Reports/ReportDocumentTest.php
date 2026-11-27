<?php

use App\Enums\Role;
use App\Filament\Admin\Resources\HearingReports\Pages\ViewHearingReport;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\HearingReport;
use App\Models\HearingReportComment;
use App\Models\User;
use App\Services\Documents\Document;
use App\Services\Documents\DocumentRenderer;
use App\Services\Documents\HearingReportDocument;
use Filament\Facades\Filament;
use Livewire\Livewire;

/*
| Hearing reports as Word and PDF files: staff who may read a report may download it; every download is logged.
*/

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function writtenReport(User $author): HearingReport
{
    $case = CourtCase::factory()->create(['court_id' => $author->monitor?->courts()->value('courts.id') ?? basicCourt()->id, 'court_case_number' => 'PKR.nr.321/2026', 'created_by' => $author->id]);
    $hearing = Hearing::factory()->forCase($case)->make(['monitor_id' => $author->monitor?->id, 'hearing_date' => '2026-11-12']);
    $hearing->created_by = $author->id;
    $hearing->save();

    return HearingReport::query()->create([
        'hearing_id' => $hearing->id,
        'title' => 'Dëgjohet dëshmitari',
        'body' => '<p>Seanca filloi në kohë.</p><p><strong>Dëshmitari</strong> u dëgjua.</p><img src="http://example.test/x.png"><script>alert(1)</script>',
        'created_by' => $author->id,
    ]);
}

/**
 * The text of a .docx (its main XML part).
 */
function docxText(string $binary): string
{
    $path = tempnam(sys_get_temp_dir(), 'docx');
    file_put_contents($path, $binary);
    $zip = new ZipArchive;
    $zip->open($path);
    $xml = (string) $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($path);

    return strip_tags(str_replace('</w:p>', "\n", $xml));
}

it('writes the report, its details and (on request) the comments as Word and PDF', function () {
    $monitor = monitorUser([basicCourt()->id]);
    $editor = userWithRole(Role::Editor);
    $report = writtenReport($monitor);
    HearingReportComment::query()->create(['hearing_report_id' => $report->id, 'user_id' => $editor->id, 'body' => 'Shtoni orën e fillimit.']);

    $documents = app(HearingReportDocument::class);
    $renderer = app(DocumentRenderer::class);

    $word = docxText($renderer->docx($documents->make($report, withComments: true)));
    expect($word)->toContain('Dëgjohet dëshmitari')
        ->toContain('PKR.nr.321/2026')
        ->toContain('12.11.2026')
        ->toContain('Seanca filloi në kohë.')
        ->toContain('Dëshmitari')
        ->toContain('Shtoni orën e fillimit.')
        ->toContain(__('documents.personal_data_notice'))
        ->not->toContain('alert(1)');

    expect(docxText($renderer->docx($documents->make($report, withComments: false))))->not->toContain('Shtoni orën e fillimit.');

    $pdf = $renderer->pdf($documents->make($report, withComments: true));
    expect($pdf)->toStartWith('%PDF-')->and(strlen($pdf))->toBeGreaterThan(2000);
});

it('keeps only simple markup from the editor', function () {
    expect(Document::cleanHtml('<p style="color:red" onclick="x()">Tekst <a href="http://x.test">lidhje</a></p><img src="http://x.test/a.png"><script>alert(1)</script><ul><li>një'))
        ->toBe('<p>Tekst lidhje</p><ul><li>një</li></ul>');
});

it('lets the author and editors download a report and logs every download', function (string $format) {
    $monitor = monitorUser([basicCourt()->id]);
    $report = writtenReport($monitor);

    $this->actingAs($monitor);
    Livewire::test(ViewHearingReport::class, ['record' => $report->getRouteKey()])
        ->callAction('download', data: ['format' => $format, 'comments' => true])
        ->assertHasNoActionErrors()
        ->assertFileDownloaded('raporti-i-seances-se-2026-11-12-pkrnr3212026.'.$format);

    $log = ExportLog::query()->latest('id')->firstOrFail();

    expect($log->resource)->toBe('hearing_report')
        ->and($log->format)->toBe($format)
        ->and($log->user_id)->toBe($monitor->id)
        ->and($log->context)->toBe(ExportLog::CONTEXT_ADMIN)
        ->and($log->filters)->toBe(['report' => $report->id, 'comments' => true])
        ->and(json_encode($log->toArray()))->not->toContain('Seanca filloi');

    $this->actingAs(userWithRole(Role::Editor));
    Livewire::test(ViewHearingReport::class, ['record' => $report->getRouteKey()])->assertActionVisible('download');
})->with(['docx', 'pdf']);

it('does not let another monitor download someone else\'s report', function () {
    $report = writtenReport(monitorUser([basicCourt()->id]));
    $other = monitorUser([basicCourt()->id]);

    expect($other->can('export', $report))->toBeFalse();
});
