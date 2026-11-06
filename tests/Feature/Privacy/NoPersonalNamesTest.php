<?php

use App\Enums\ExportFormat;
use App\Filament\Research\Pages\Datasets;
use App\Livewire\Portal\CaseSearch;
use App\Livewire\Portal\Statistics;
use App\Services\Export\ResearchExportColumns;
use App\Services\Portal\PortalStatistics;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Feature\Privacy\PersonalDataFixture;

/*
| Proposal B7: "an automated test proving that no public or researcher route or API
| response contains personal-name fields". Every GET route of the public portal, the
| researcher area and the researcher API is discovered automatically; a route with an
| unknown parameter fails the test so that new routes must be added here.
*/

function publicUrls(array $fixture): array
{
    $values = [
        'publicRef' => array_map(fn ($case) => $case->public_ref, $fixture['cases']),
        'slug' => [$fixture['publication']->slug],
        'dataset' => array_keys(PortalStatistics::datasets()),
    ];

    $urls = [];

    /** @var RoutingRoute $route */
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'portal.') || ! in_array('GET', $route->methods(), true)) {
            continue;
        }

        if ($name === 'portal.publications.download') {
            continue; // binary PDF without database content
        }

        $parameters = array_diff($route->parameterNames(), ['locale', 'page']);
        $unknown = array_diff($parameters, array_keys($values));
        expect($unknown)->toBeEmpty("Route {$name} has parameters this privacy test does not know: ".implode(', ', $unknown));

        foreach ((array) config('courtwatch.supported_locales') as $locale) {
            $combinations = [[]];

            foreach ($parameters as $parameter) {
                $next = [];
                foreach ($combinations as $combination) {
                    foreach ($values[$parameter] as $value) {
                        $next[] = [...$combination, $parameter => $value];
                    }
                }
                $combinations = $next;
            }

            foreach ($combinations as $combination) {
                $urls[] = in_array('locale', $route->parameterNames(), true)
                    ? route($name, ['locale' => $locale, ...$combination])
                    : route($name, $combination);
            }
        }
    }

    return array_values(array_unique($urls));
}

it('never shows personal names or case numbers on any public page', function () {
    $fixture = PersonalDataFixture::create();
    $urls = publicUrls($fixture);

    expect(count($urls))->toBeGreaterThan(20);

    foreach ($urls as $url) {
        $response = $this->get($url);
        expect($response->status())->toBeLessThan(400, "{$url} returned {$response->status()}");

        $body = $response->baseResponse instanceof StreamedResponse
            ? (string) $response->streamedContent()
            : (string) $response->getContent();
        PersonalDataFixture::assertClean($body, $url);
    }
});

it('never shows personal names in the interactive portal components', function () {
    PersonalDataFixture::create();

    foreach (['sq', 'en'] as $locale) {
        app()->setLocale($locale);
        PersonalDataFixture::assertClean(Livewire::test(Statistics::class)->html(), 'statistics component');
        PersonalDataFixture::assertClean(Livewire::test(CaseSearch::class)->html(), 'case search');
        PersonalDataFixture::assertClean(Livewire::test(CaseSearch::class)->set('q', 'Xhafolli')->html(), 'case search by name');
        PersonalDataFixture::assertClean(Livewire::test(Statistics::class)->set('type', 'criminal_corruption')->html(), 'filtered statistics');
    }
});

it('does not let anyone find a case by a party name on the portal', function () {
    PersonalDataFixture::create();

    Livewire::test(CaseSearch::class)
        ->set('q', 'Xhafolli')
        ->assertViewHas('cases', fn ($cases) => $cases->total() === 0);
});

/**
 * JSON with unescaped unicode and slashes, so "ë" and "/" in names and case numbers match.
 */
function readableJson(mixed $data): string
{
    return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

it('never shows personal names or case numbers in the researcher area', function () {
    PersonalDataFixture::create();
    $this->actingAs(researcher());

    $visited = 0;

    /** @var RoutingRoute $route */
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'filament.research.') || ! in_array('GET', $route->methods(), true)) {
            continue;
        }

        if ($name === 'filament.research.auth.password-reset.reset') {
            continue; // needs a signed link from the reset e-mail; a guest form without data
        }

        expect($route->parameterNames())->toBeEmpty("Route {$name} has parameters this privacy test does not know");

        $response = $this->get(route($name));
        expect($response->status())->toBeLessThan(400, "{$name} returned {$response->status()}");
        PersonalDataFixture::assertClean((string) $response->getContent(), $name);
        $visited++;
    }

    expect($visited)->toBeGreaterThanOrEqual(5);
});

it('never puts personal names, case numbers or free text in research downloads', function (string $dataset) {
    PersonalDataFixture::create();
    $user = researcher();

    foreach ([ExportFormat::Csv, ExportFormat::Xlsx] as $format) {
        $response = Datasets::streamDataset($dataset, $format, $user);

        ob_start();
        $response->sendContent();
        $file = (string) ob_get_clean();

        if ($format === ExportFormat::Xlsx) {
            $path = tempnam(sys_get_temp_dir(), 'xlsx');
            file_put_contents($path, $file);
            $zip = new ZipArchive;
            $zip->open($path);
            $file = (string) $zip->getFromName('xl/sharedStrings.xml').(string) $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            unlink($path);
        }

        expect(strlen($file))->toBeGreaterThan(100);
        PersonalDataFixture::assertClean($file, "{$dataset}.{$format->value}");
    }

    $columns = match ($dataset) {
        'cases' => ResearchExportColumns::cases(),
        'parties' => ResearchExportColumns::parties(),
        default => ResearchExportColumns::hearings(),
    };
    $header = array_keys($columns);
    foreach (['name', 'court_case_number', 'indictment_number', 'police_case_number', 'judge', 'prosecutor', 'monitor', 'notes', 'summary', 'security_comment'] as $forbidden) {
        expect($header)->not->toContain($forbidden);
    }
})->with(['cases', 'parties', 'hearings']);

it('never returns personal names or case numbers from the researcher API', function () {
    $fixture = PersonalDataFixture::create();
    $token = researcher()->createToken('privacy', ['research:read'])->plainTextToken;

    $values = ['ref' => array_map(fn ($case) => $case->public_ref, $fixture['cases'])];
    $checked = 0;

    /** @var RoutingRoute $route */
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! str_starts_with($name, 'api.v1.')) {
            continue;
        }

        $parameters = $route->parameterNames();
        $unknown = array_diff($parameters, array_keys($values));
        expect($unknown)->toBeEmpty("Route {$name} has parameters this privacy test does not know: ".implode(', ', $unknown));

        $combinations = $parameters === [] ? [[]] : array_map(fn (string $ref): array => ['ref' => $ref], $values['ref']);

        foreach ($combinations as $combination) {
            foreach ((array) config('courtwatch.supported_locales') as $locale) {
                $url = route($name, [...$combination, 'lang' => $locale, 'per_page' => 100]);
                $response = $this->withToken($token)->getJson($url);

                expect($response->status())->toBe(200, "{$url} returned {$response->status()}");
                PersonalDataFixture::assertClean(readableJson($response->json()), $url);
                $checked++;
            }
        }
    }

    expect($checked)->toBeGreaterThanOrEqual(16);
});

it('never exposes personal names in the API documentation', function () {
    PersonalDataFixture::create();

    foreach (['/docs/api', '/docs/api.openapi', '/docs/api.postman'] as $url) {
        PersonalDataFixture::assertClean((string) $this->get($url)->assertOk()->getContent(), $url);
    }
});
