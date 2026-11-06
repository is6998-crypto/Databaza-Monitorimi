<?php

use App\Enums\WorkflowStatus;
use App\Livewire\Portal\CaseSearch;
use App\Livewire\Portal\Statistics;
use App\Models\CourtCase;
use App\Models\ExportLog;
use App\Models\Hearing;
use App\Models\Publication;
use App\Services\Anonymizer;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('redirects the root to the preferred language', function () {
    $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertRedirect('/en');
    $this->get('/', ['Accept-Language' => 'de'])->assertRedirect('/sq');
});

it('serves every page in Albanian and English with SEO tags', function (string $locale, string $path, string $text) {
    $this->get("/{$locale}{$path}")
        ->assertOk()
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('hreflang="sq"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee($text);
})->with([
    ['sq', '', 'Monitorimi i gjykatave'],
    ['en', '', 'Court monitoring in Kosovo'],
    ['sq', '/cases', 'Kërko lëndët e monitoruara'],
    ['en', '/reports', 'Reports & publications'],
    ['sq', '/methodology', 'Metodologjia'],
    ['en', '/about', 'About the programme'],
    ['sq', '/data-protection', '06/L-082'],
    ['en', '/open-data', 'Open data'],
]);

it('rejects unknown languages', function () {
    $this->get('/de')->assertNotFound();
});

it('publishes a sitemap with both languages', function () {
    $case = CourtCase::factory()->approved()->create();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(url('/sq/methodology'), false)
        ->assertSee(url('/en/methodology'), false)
        ->assertSee(url('/sq/cases/'.$case->public_ref), false);
});

it('shows only verified cases in the public case search and detail', function () {
    $approved = CourtCase::factory()->approved()->create();
    $draft = CourtCase::factory()->create();

    Livewire::test(CaseSearch::class)
        ->assertSee($approved->public_code)
        ->assertDontSee($draft->public_code);

    $this->get('/sq/cases/'.$approved->public_ref)->assertOk()->assertSee($approved->public_code);
    $this->get('/sq/cases/'.$draft->public_ref)->assertNotFound();
});

it('suppresses aggregate cells under five', function () {
    expect(Anonymizer::suppress(4))->toBeNull()
        ->and(Anonymizer::suppress(0))->toBeNull()
        ->and(Anonymizer::suppress(5))->toBe(5)
        ->and(Anonymizer::rate(4, 100))->toBeNull()
        ->and(Anonymizer::rate(10, 4))->toBeNull()
        ->and(Anonymizer::rate(10, 40))->toBe(25.0)
        ->and(Anonymizer::statistic(120.5, 3))->toBeNull()
        ->and(Anonymizer::display(null))->toBe('<5');
});

it('hides small numbers in the public statistics', function () {
    $case = CourtCase::factory()->approved()->create();
    foreach (range(1, 3) as $n) {
        $hearing = Hearing::factory()->forCase($case)->make(['hearing_date' => now()->subDays($n)->toDateString()]);
        $hearing->status = WorkflowStatus::Approved;
        $hearing->save();
    }

    Livewire::test(Statistics::class)
        ->assertViewHas('headline', fn (array $headline) => $headline['hearings'] === null && $headline['cases'] === null)
        ->assertSeeHtml('data-stat="hearings">&lt;5<');
});

it('offers aggregate open-data CSVs and logs each download', function () {
    $response = $this->get('/en/open-data/cases-by-type.csv')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->getContent())->toContain('Type of case')
        ->and(ExportLog::query()->where('context', 'public')->where('resource', 'open-data:cases-by-type')->exists())->toBeTrue();

    $this->get('/en/open-data/unknown.csv')->assertNotFound();
});

it('lists published reports and serves their files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('publications/r.pdf', '%PDF-1.4');
    $published = Publication::query()->create(['slug' => 'r', 'type' => 'report', 'title_sq' => 'Raporti R', 'title_en' => 'Report R', 'published_on' => now()->subDay(), 'is_published' => true, 'file_disk' => 'local', 'file_path' => 'publications/r.pdf']);
    Publication::query()->create(['slug' => 'hidden', 'type' => 'report', 'title_sq' => 'I fshehur', 'published_on' => now()->subDay(), 'is_published' => false]);

    $this->get('/en/reports')->assertOk()->assertSee('Report R')->assertDontSee('I fshehur');
    $this->get('/sq/reports/r')->assertOk()->assertSee('Raporti R');
    $this->get('/sq/reports/r/download')->assertOk();
    $this->get('/sq/reports/hidden')->assertNotFound();
});

it('keeps the language of Livewire updates', function () {
    app()->setLocale('en');

    Livewire::test(CaseSearch::class)
        ->assertSet('locale', 'en')
        ->set('type', 'civil')
        ->assertSee('Offence or article');
});
