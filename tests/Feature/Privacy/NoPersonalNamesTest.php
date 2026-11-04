<?php

use App\Livewire\Portal\CaseSearch;
use App\Livewire\Portal\Statistics;
use App\Services\Portal\PortalStatistics;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Feature\Privacy\PersonalDataFixture;

/*
| Proposal B7: "an automated test proving that no public or researcher route or API
| response contains personal-name fields". Every GET route of the public portal is
| discovered automatically; a route with an unknown parameter fails the test so that new
| routes must be added here.
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
