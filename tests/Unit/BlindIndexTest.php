<?php

use App\Support\BlindIndex;

it('normalises diacritics, case and punctuation', function () {
    expect(BlindIndex::normalize('  Gëzim  ÇAKAJ-Berisha '))->toBe('gezim cakaj berisha');
});

it('produces the same hash for differently written names', function () {
    expect(BlindIndex::fullName('Arbën Krasniqi'))->toBe(BlindIndex::fullName('ARBEN   krasniqi'))
        ->and(BlindIndex::fullName('Arbën Krasniqi'))->not->toBe(BlindIndex::fullName('Arben Gashi'));
});

it('never contains the plain name', function () {
    $tokens = (string) BlindIndex::tokenString('Arbën Krasniqi');

    expect($tokens)->not->toContain('arben')
        ->and($tokens)->not->toContain('krasniqi')
        ->and(explode(' ', $tokens))->toHaveCount(2);
});

it('finds a name by any of its tokens', function () {
    $stored = explode(' ', (string) BlindIndex::tokenString('Arbën Krasniqi'));

    expect($stored)->toContain(BlindIndex::queryTokenHashes('krasniqi')[0]);
});

it('ignores empty names', function () {
    expect(BlindIndex::fullName('   '))->toBeNull()
        ->and(BlindIndex::tokenString(null))->toBeNull();
});
