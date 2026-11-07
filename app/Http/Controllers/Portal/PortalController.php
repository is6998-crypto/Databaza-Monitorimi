<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Publication;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Export\ExportService;
use App\Services\Portal\PortalStatistics;
use App\Services\Portal\PublicCasePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public portal pages (proposal B6.7). Only verified, anonymized data; never personal names.
 */
class PortalController extends Controller
{
    public const PAGES = ['methodology', 'about', 'data-protection'];

    public function redirect(Request $request): RedirectResponse
    {
        $supported = (array) config('courtwatch.supported_locales');
        $preferred = $request->getPreferredLanguage($supported) ?? (string) config('app.locale');

        return redirect()->route('portal.home', ['locale' => in_array($preferred, $supported, true) ? $preferred : config('app.locale')]);
    }

    public function home(): View
    {
        $recent = CourtCase::query()->verified()->with(PublicCasePresenter::relations())->latest('updated_at')->limit(3)->get();

        return view('portal.home', [
            'headline' => PortalStatistics::for(new AnalyticsFilters)->headline(),
            'publications' => Publication::query()->published()->latest('published_on')->limit(3)->get(),
            // The hero shows the real product: the latest verified cases as the case search lists them
            // (anonymized by the same presenter), and the month of the latest update (month and year only).
            'recentCases' => $recent->map(fn (CourtCase $case): array => PublicCasePresenter::summary($case)),
            'lastUpdate' => $recent->first()?->updated_at?->timezone((string) config('courtwatch.display_timezone', 'Europe/Belgrade'))->format('m.Y'),
        ]);
    }

    public function cases(): View
    {
        return view('portal.cases');
    }

    public function case(string $locale, string $publicRef): View
    {
        $case = CourtCase::query()->verified()->where('public_ref', $publicRef)->with(PublicCasePresenter::relations())->firstOrFail();

        return view('portal.case', ['case' => PublicCasePresenter::detail($case)]);
    }

    public function publications(): View
    {
        return view('portal.publications', [
            'publications' => Publication::query()->published()->latest('published_on')->paginate(12),
        ]);
    }

    public function publication(string $locale, string $slug): View
    {
        return view('portal.publication', [
            'publication' => Publication::query()->published()->where('slug', $slug)->firstOrFail(),
        ]);
    }

    public function download(string $locale, string $slug): StreamedResponse
    {
        $publication = Publication::query()->published()->where('slug', $slug)->firstOrFail();

        abort_if(blank($publication->file_path) || blank($publication->file_disk), 404);
        abort_unless(Storage::disk((string) $publication->file_disk)->exists((string) $publication->file_path), 404);

        return Storage::disk((string) $publication->file_disk)->download((string) $publication->file_path, $publication->slug.'.pdf');
    }

    public function openData(): View
    {
        return view('portal.open-data', ['datasets' => PortalStatistics::datasets()]);
    }

    /**
     * Aggregate CSV with small-cell suppression; every download is logged.
     */
    public function dataset(string $locale, string $dataset, ExportService $exports): Response
    {
        abort_unless(array_key_exists($dataset, PortalStatistics::datasets()), 404);

        $data = PortalStatistics::for(new AnalyticsFilters)->dataset($dataset);
        $exports->log('open-data:'.$dataset, 'csv', ExportLog::CONTEXT_PUBLIC, null, ['locale' => $locale], count($data['rows']));

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $data['header'], escape: '\\');

        foreach ($data['rows'] as $row) {
            fputcsv($handle, $row, escape: '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="courtwatch-'.$dataset.'-'.$locale.'.csv"',
        ]);
    }

    public function page(string $locale, string $page): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('portal.page', ['page' => $page]);
    }

    public function sitemap(): Response
    {
        $urls = [];

        foreach ((array) config('courtwatch.supported_locales') as $locale) {
            foreach (['portal.home', 'portal.cases', 'portal.publications', 'portal.open-data', 'portal.methodology', 'portal.about', 'portal.data-protection'] as $route) {
                $urls[] = route($route, ['locale' => $locale]);
            }

            foreach (Publication::query()->published()->pluck('slug') as $slug) {
                $urls[] = route('portal.publications.show', ['locale' => $locale, 'slug' => $slug]);
            }

            foreach (CourtCase::query()->verified()->latest('updated_at')->limit(2000)->pluck('public_ref') as $ref) {
                $urls[] = route('portal.cases.show', ['locale' => $locale, 'publicRef' => $ref]);
            }
        }

        return response()->view('portal.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }
}
