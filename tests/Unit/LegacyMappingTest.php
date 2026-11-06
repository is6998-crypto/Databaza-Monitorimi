<?php

use App\Enums\HearingType;
use App\Enums\PostponementReason;
use App\Enums\StageOutcome;
use Migration\Import\CaseGrouper;
use Migration\Support\Text;
use Migration\Support\ValueMapper;

/*
| Legacy importer building blocks: text normalisation, value mapping and case grouping.
*/

it('cleans legacy text and treats placeholders as empty', function (mixed $raw, ?string $clean) {
    expect(Text::clean($raw))->toBe($clean);
})->with([
    ['  Gjykata   Themelore  ', 'Gjykata Themelore'],
    ['-', null],
    ['N/A', null],
    ['0000-00-00', null],
    ['', null],
    [null, null],
    [42, '42'],
]);

it('repairs UTF-8 stored through a Latin-1 connection', function () {
    expect(Text::fixEncoding('PrishtinÃ«, GraÃ§anicÃ«, Ã‡ARR'))->toBe('Prishtinë, Graçanicë, ÇARR')
        ->and(Text::fixEncoding('Prishtinë'))->toBe('Prishtinë');
});

it('builds matching keys for names, numbers and places', function () {
    expect(Text::key('Gjykata Themelore – Prishtinë'))->toBe('gjykata themelore prishtine')
        ->and(Text::personKey('Z. Arben  KRASNIQI'))->toBe(Text::personKey('Krasniqi Arben'))
        ->and(Text::caseNumberKey('P.nr. 123/2019'))->toBe(Text::caseNumberKey('p nr 123/2019'))
        ->and(Text::stem('prishtines'))->toBe(Text::stem('prishtina'))
        ->and(Text::stem('gjakoves'))->toBe(Text::stem('gjakova'))
        ->and(Text::stem('ferizajt'))->toBe('ferizaj')
        ->and(Text::split('A, B; C / D'))->toBe(['A', 'B', 'C', 'D']);
});

it('maps legacy booleans, dates and amounts', function () {
    $values = ValueMapper::fromConfig();

    expect($values->bool('PO'))->toBeTrue()
        ->and($values->bool('jo'))->toBeFalse()
        ->and($values->bool('e mbyllur'))->toBeFalse()
        ->and($values->bool('ndoshta'))->toBeNull()
        ->and($values->date('15.03.2021'))->toBe('2021-03-15')
        ->and($values->date('15/03/2021'))->toBe('2021-03-15')
        ->and($values->date('2021-03-15 10:20:00'))->toBe('2021-03-15')
        ->and($values->date('2022-02-30'))->toBeNull()
        ->and($values->date('1899-01-01'))->toBeNull()
        ->and($values->date('jo e ditur'))->toBeNull()
        ->and($values->amount('1.500,00 €'))->toBe(1500.0)
        ->and($values->amount('12.500'))->toBe(12500.0)
        ->and($values->amount('1,250.50'))->toBe(1250.5)
        ->and($values->amount('abc'))->toBeNull()
        ->and($values->int('45 min', 0, 600))->toBe(45)
        ->and($values->int('9999', 0, 600))->toBeNull();
});

it('maps free text to enum values through labels and synonyms', function () {
    $values = ValueMapper::fromConfig();

    expect($values->enum(HearingType::class, 'Shqyrtimi gjyqësor'))->toBe(HearingType::MainTrial)
        ->and($values->enum(HearingType::class, 'MAIN TRIAL'))->toBe(HearingType::MainTrial)
        ->and($values->enum(HearingType::class, 'main_trial'))->toBe(HearingType::MainTrial)
        ->and($values->enum(HearingType::class, 'seancë e çuditshme'))->toBeNull()
        ->and($values->enum(StageOutcome::class, 'po', 'stage_outcome.plea_agreement'))->toBe(StageOutcome::Accepted)
        ->and($values->enum(StageOutcome::class, 'po'))->toBe(StageOutcome::Approved)
        ->and($values->enums(PostponementReason::class, 'Mungesa e prokurorit; mungesa e të akuzuarit, pa arsye'))
        ->toBe([['prosecutor', 'accused'], ['pa arsye']]);
});

it('groups rows that share any case number into one case', function () {
    $grouper = new CaseGrouper;
    $grouper->add('1', 'court:1', ['court_case_number' => null, 'indictment_number' => 'PP 55/2020']);
    $grouper->add('2', 'court:1', ['court_case_number' => 'P.nr. 101/2021', 'indictment_number' => 'PP55/2020']);
    $grouper->add('3', 'court:1', ['court_case_number' => 'P nr 101/2021', 'indictment_number' => null]);
    $grouper->add('4', 'court:2', ['court_case_number' => 'P.nr. 101/2021', 'indictment_number' => null]); // same number, other court
    $grouper->add('5', 'court:1', ['court_case_number' => '0', 'indictment_number' => '-']); // placeholders are ignored
    $grouper->add('6', 'court:1', ['court_case_number' => '0', 'indictment_number' => null]);

    expect($grouper->groups())->toBe([['1', '2', '3'], ['4'], ['5'], ['6']]);
});
