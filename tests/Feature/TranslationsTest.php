<?php

use Illuminate\Support\Arr;

/*
| Every user-facing string exists in each language (Albanian, English, Serbian), with the same placeholders.
*/

/**
 * @return array<string, string> dotted key => text
 */
function translationLines(string $locale): array
{
    $lines = [];

    foreach (glob(lang_path("{$locale}/*.php")) ?: [] as $file) {
        $group = basename($file, '.php');

        foreach (Arr::dot(require $file) as $key => $value) {
            $lines["{$group}.{$key}"] = is_array($value) ? '' : (string) $value;
        }
    }

    return $lines;
}

/**
 * @return list<string>
 */
function placeholders(string $text): array
{
    preg_match_all('/:([a-z_]+)/', $text, $matches);
    $found = array_values(array_unique($matches[1]));
    sort($found);

    return $found;
}

it('has the same keys in every language', function (string $locale) {
    $english = translationLines('en');
    $other = translationLines($locale);

    expect(array_keys(array_diff_key($english, $other)))->toBe([], "Missing in {$locale}")
        ->and(array_keys(array_diff_key($other, $english)))->toBe([], "Only in {$locale}");
})->with(['sq', 'sr']);

it('keeps the placeholders of every string', function (string $locale) {
    $english = translationLines('en');
    $other = translationLines($locale);
    $wrong = [];

    foreach ($english as $key => $text) {
        if (isset($other[$key]) && placeholders($text) !== placeholders($other[$key])) {
            $wrong[] = $key;
        }
    }

    expect($wrong)->toBe([]);
})->with(['sq', 'sr']);
