<?php

namespace Migration\Support;

use Illuminate\Support\Str;

/**
 * Cleaning and normalisation of legacy text values.
 */
final class Text
{
    /** Values that mean "nothing was entered". */
    private const EMPTY = ['-', '--', '---', '/', '.', '?', 'n/a', 'na', 'null', 'none', 'nuk dihet', 'e panjohur', '0000-00-00', '0000-00-00 00:00:00'];

    /** Titles stripped from person names before matching. */
    private const TITLES = ['z', 'znj', 'zj', 'dr', 'mr', 'prof', 'av', 'avokat', 'gjyqtari', 'gjyqtarja', 'gjyqtar', 'prokurori', 'prokurorja', 'prokuror', 'judge', 'prosecutor'];

    /**
     * Trimmed text with single spaces and repaired encoding, or null when empty.
     */
    public static function clean(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        $text = self::fixEncoding(trim((string) $value));
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '' || in_array(mb_strtolower($text), self::EMPTY, true)) {
            return null;
        }

        return $text;
    }

    /**
     * Repairs UTF-8 text that was stored through a Latin-1 connection ("PrishtinÃ«" → "Prishtinë").
     */
    public static function fixEncoding(string $value): string
    {
        if (! str_contains($value, 'Ã') && ! str_contains($value, 'Â')) {
            return $value;
        }

        $repaired = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        if (mb_check_encoding($repaired, 'UTF-8') && ! str_contains($repaired, 'Ã')) {
            return $repaired;
        }

        return $value;
    }

    /**
     * Matching key: lower case ASCII letters and digits separated by single spaces
     * ("Gjykata Themelore – Prishtinë" → "gjykata themelore prishtine").
     */
    public static function key(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = Str::lower(Str::ascii(self::fixEncoding($value)));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    /**
     * Key for a person's name: titles removed and name parts sorted, so that
     * "Krasniqi Arben" and "Z. Arben  KRASNIQI" match.
     */
    public static function personKey(?string $value): string
    {
        $tokens = array_values(array_filter(
            explode(' ', self::key($value)),
            fn (string $token): bool => $token !== '' && ! in_array($token, self::TITLES, true),
        ));
        sort($tokens);

        return implode(' ', $tokens);
    }

    /**
     * Key for a case number: upper case, without spaces, dots or dashes
     * ("P.nr. 123/2019" and "P nr 123/2019" → "PNR123/2019").
     */
    public static function caseNumberKey(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) preg_replace('/[^A-Z0-9\/]+/', '', Str::upper(Str::ascii(self::fixEncoding($value))));
    }

    /**
     * Strips common Albanian case endings so inflected place names match
     * ("prishtines", "prishtine", "prishtina" → "prishtin").
     */
    public static function stem(string $token): string
    {
        foreach (['it', 'es', 'ut', 'e', 'a', 'i', 'u', 't'] as $ending) {
            if (strlen($token) > strlen($ending) + 2 && str_ends_with($token, $ending)) {
                return substr($token, 0, -strlen($ending));
            }
        }

        return $token;
    }

    /**
     * Splits a multi-valued field ("A, B; C / D").
     *
     * @return list<string>
     */
    public static function split(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $parts = preg_split('/\s*[,;|\/\n]+\s*/u', $value) ?: [];

        return array_values(array_filter(array_map(fn (string $part): ?string => self::clean($part), $parts)));
    }
}
