<?php

namespace App\Services\Documents;

use App\Models\ExportLog;
use App\Models\HearingReport;
use App\Models\HearingReportComment;
use App\Models\User;
use App\Services\Export\ExportService;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A hearing report as a Word or PDF file, for the staff who may read it (to prepare a publication, to print).
 * The file names people, so it says so at the top; every download is logged like any other export, without the
 * text.
 */
class HearingReportDocument
{
    public function __construct(private DocumentRenderer $renderer, private ExportService $exports) {}

    public function make(HearingReport $report, bool $withComments): Document
    {
        $report->loadMissing(['hearing.case.court', 'author', 'reviewer', 'comments.user']);
        $hearing = $report->hearing;
        $date = (string) config('courtwatch.date_format');
        $zone = (string) config('courtwatch.display_timezone');

        $document = (new Document(
            $report->title ?: __('reports.model'),
            __('reports.title', ['date' => $hearing->hearing_date->format($date), 'case' => $hearing->case->display_label]),
        ))
            ->notice(__('documents.personal_data_notice'))
            ->meta(__('hearings.fields.case'), $hearing->case->court_case_number ?: '#'.$hearing->case_id)
            ->meta(__('hearings.fields.court'), $hearing->case->court?->label())
            ->meta(__('hearings.fields.hearing_date'), $hearing->hearing_date->format($date))
            ->meta(__('hearings.fields.hearing_type'), $hearing->hearing_type?->getLabel())
            ->meta(__('reports.fields.author'), $report->author?->name)
            ->meta(__('reports.fields.status'), $report->status->getLabel())
            ->meta(__('reports.fields.reviewer'), $report->reviewer !== null
                ? $report->reviewer->name.($report->reviewed_at ? ', '.$report->reviewed_at->timezone($zone)->format($date) : '')
                : null)
            ->meta(__('documents.generated'), now($zone)->format((string) config('courtwatch.datetime_format')));

        $document->heading(__('reports.fields.body'));

        if (blank($report->plainText())) {
            $document->paragraph(__('reports.empty_body'), muted: true);
        } else {
            $document->html($report->body);
        }

        if ($withComments) {
            $document->heading(__('reports.fields.comments'));

            if ($report->comments->isEmpty()) {
                $document->paragraph(__('documents.no_comments'), muted: true);
            }

            foreach ($report->comments->sortBy('created_at') as $comment) {
                /** @var HearingReportComment $comment */
                $document->comment(
                    collect([$comment->user->name ?? '—', $comment->created_at?->timezone($zone)->format((string) config('courtwatch.datetime_format'))])->filter()->implode(' · '),
                    $comment->body,
                );
            }
        }

        return $document;
    }

    public function download(HearingReport $report, string $format, bool $withComments, ?User $user): StreamedResponse
    {
        $document = $this->make($report, $withComments);
        $binary = $format === 'pdf' ? $this->renderer->pdf($document) : $this->renderer->docx($document);

        $this->exports->log('hearing_report', $format, ExportLog::CONTEXT_ADMIN, $user, ['report' => $report->id, 'comments' => $withComments], 1);

        $name = Str::slug(Str::limit(__('reports.title', [
            'date' => $report->hearing->hearing_date->format('Y-m-d'),
            'case' => $report->hearing->case->court_case_number ?: '#'.$report->hearing->case_id,
        ]), 120, '')).'.'.$format;

        return response()->streamDownload(fn () => print ($binary), $name, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
