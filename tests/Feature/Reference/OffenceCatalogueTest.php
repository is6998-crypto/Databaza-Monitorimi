<?php

use App\Enums\ChargeStage;
use App\Enums\Role;
use App\Filament\Admin\Widgets\Analytics\CasesByCategoryChart;
use App\Livewire\Portal\Statistics;
use App\Models\CaseParty;
use App\Models\Charge;
use App\Models\CourtCase;
use App\Models\Reference\Category;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Services\Analytics\AnalyticsFilters;
use App\Services\Analytics\AnalyticsService;
use App\Services\Portal\PortalStatistics;
use App\Services\Reference\OffenceCatalogue;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * The full list of criminal offences and their statistics categories (database/data).
 */
function catalogueRows(string $file): int
{
    return count(file(database_path('data/'.$file), FILE_SKIP_EMPTY_LINES)) - 1;
}

it('loads every offence of the list with its code, both titles and its category', function () {
    expect(CriminalOffence::query()->count())->toBe(catalogueRows('criminal-offences.csv'))
        ->and(Category::query()->where('is_active', true)->count())->toBe(catalogueRows('offence-categories.csv'))
        ->and(CriminalCode::query()->count())->toBe(catalogueRows('criminal-codes.csv'))
        ->and(CriminalOffence::query()->whereNull('category_id')->exists())->toBeFalse()
        ->and(CriminalOffence::query()->whereNull('title_en')->exists())->toBeFalse();

    $offence = CriminalOffence::query()->whereHas('criminalCode', fn ($query) => $query->where('code', 'KPRK-2019'))->where('article_number', '414')->firstOrFail();

    expect($offence->title_sq)->toBe('Keqpërdorimi i pozitës apo autoritetit zyrtar')
        ->and($offence->title_en)->toBe('Abuse of official position or authority')
        ->and($offence->category?->code)->toBe('KORR')
        ->and($offence->label('en'))->toBe('KPRK-2019 art. 414 — Abuse of official position or authority')
        ->and($offence->category?->label('en'))->toBe('Official corruption and criminal offences against official duty');
});

it('offers only named offences in the pickers', function () {
    $unnamed = CriminalOffence::query()->where('category_id', Category::query()->where('code', 'PE')->value('id'))->where('is_active', false)->get();

    expect($unnamed)->not->toBeEmpty()
        ->and(array_intersect(array_keys(CriminalOffence::selectOptions()), $unnamed->modelKeys()))->toBe([]);
});

it('syncs again without changes and sets offences outside the list inactive', function () {
    $code = CriminalCode::query()->where('code', 'KPK-2012')->firstOrFail();
    $extra = CriminalOffence::query()->create(['criminal_code_id' => $code->id, 'article_number' => '9999', 'title_sq' => 'Jo në listë']);
    CriminalOffence::query()->where('article_number', '414')->update(['title_sq' => 'Ndryshuar']);

    $result = (new OffenceCatalogue)->sync();

    expect($result['created'])->toBe(0)
        ->and($result['deactivated'])->toBe(1)
        ->and($extra->fresh()->is_active)->toBeFalse()
        ->and(CriminalOffence::query()->where('article_number', '414')->where('title_sq', 'Ndryshuar')->exists())->toBeFalse();
});

it('merges the earlier categories into the new ones and retires any other', function () {
    $code = CriminalCode::query()->where('code', 'KPK-2012')->firstOrFail();
    $corruption = Category::query()->create(['name_sq' => 'Korrupsion', 'name_en' => 'Corruption']);
    $unknown = Category::query()->create(['name_sq' => 'Kategori e vjetër']);
    $offence = CriminalOffence::query()->create(['criminal_code_id' => $code->id, 'article_number' => '9998', 'title_sq' => 'Jo në listë', 'category_id' => $corruption->id]);

    (new OffenceCatalogue)->sync();

    expect($offence->fresh()->category?->code)->toBe('KORR')
        ->and(Category::withTrashed()->find($corruption->id)?->trashed())->toBeTrue()
        ->and($unknown->fresh()->is_active)->toBeFalse()
        ->and(Category::query()->where('is_active', true)->whereNull('code')->exists())->toBeFalse();
});

it('counts cases per offence category, suppressed on the portal', function () {
    $category = Category::query()->where('code', 'KORR')->firstOrFail();
    $offence = $category->offences()->firstOrFail();

    foreach (range(1, 6) as $n) {
        $case = CourtCase::factory()->approved()->create();
        $party = CaseParty::factory()->create(['case_id' => $case->id]);
        Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Indictment]);
    }

    $drug = Category::query()->where('code', 'DR')->firstOrFail();
    $case = CourtCase::factory()->approved()->create();
    $party = CaseParty::factory()->create(['case_id' => $case->id]);
    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $drug->offences()->value('id'), 'stage' => ChargeStage::Indictment]);

    $draft = CourtCase::factory()->create();
    $party = CaseParty::factory()->create(['case_id' => $draft->id]);
    Charge::query()->create(['case_party_id' => $party->id, 'criminal_offence_id' => $offence->id, 'stage' => ChargeStage::Indictment]);

    app()->setLocale('en');

    expect(AnalyticsService::for(new AnalyticsFilters)->casesByCategory())->toBe([(string) $category->id => 6, (string) $drug->id => 1])
        ->and(PortalStatistics::for(new AnalyticsFilters)->casesByCategory())->toBe([$category->name_en => 6, $drug->name_en => null]);

    Livewire::test(Statistics::class)->assertSee($category->name_en)->assertSee('Monitored cases by offence category');

    $this->get('/en/open-data/cases-by-category.csv')->assertOk()->assertSee('Offence category')->assertSee('"'.$drug->name_en.'",<5', false);

    $this->actingAs(userWithRole(Role::Analyst));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(CasesByCategoryChart::class)->assertOk()->assertSee('Cases by offence category');
});
