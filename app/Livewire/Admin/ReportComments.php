<?php

namespace App\Livewire\Admin;

use App\Models\HearingReport;
use App\Models\User;
use App\Services\HearingReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The comment thread under a hearing report: the editor's remarks and the monitor's answers, oldest first.
 * Viewing follows the report's policy; writing follows HearingReportPolicy::comment().
 */
class ReportComments extends Component
{
    #[Locked]
    public int $reportId;

    public string $body = '';

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;
        Gate::authorize('view', $this->report());
    }

    private function report(): HearingReport
    {
        return HearingReport::query()->with('hearing')->findOrFail($this->reportId);
    }

    public function post(HearingReportService $service): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->validate(['body' => ['required', 'string', 'max:5000']], attributes: ['body' => __('reports.comments.field')]);

        $service->comment($this->report(), $user, $this->body);

        $this->reset('body');
    }

    public function render(): View
    {
        $report = $this->report();
        Gate::authorize('view', $report);

        return view('filament.admin.reports.comments', [
            'comments' => $report->comments()->with('user.roles')->get(),
            'canComment' => Gate::allows('comment', $report),
            'authorId' => $report->created_by,
        ]);
    }
}
