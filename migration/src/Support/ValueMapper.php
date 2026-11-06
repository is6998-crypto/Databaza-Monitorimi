<?php

namespace Migration\Support;

use BackedEnum;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Support\Str;

/**
 * Turns legacy free text into codes, booleans, dates and numbers.
 *
 * Every enum matches its own value and its Albanian and English labels; extra
 * spellings come from config('legacy.values'). A result of null for a non-empty
 * input means "could not be mapped" (the caller sends it to review).
 */
final class ValueMapper
{
    private const DATE_FORMATS = ['Y-m-d', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd.m.Y', 'd.m.Y H:i', 'd.m.Y H:i:s', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.y', 'j.n.Y', 'j.n.y'];

    /** @var array<string, array<string, string>> enum class => key => value */
    private array $enumTables = [];

    /** @var array<string, bool> */
    private array $booleans;

    /**
     * @param  array<string, array<string, mixed>>  $synonyms  config('legacy.values')
     * @param  list<string>  $locales
     */
    public function __construct(private readonly array $synonyms, private readonly array $locales = ['sq', 'en'])
    {
        $this->booleans = [];

        foreach ((array) ($synonyms['boolean'] ?? []) as $key => $value) {
            $this->booleans[Text::key((string) $key)] = (bool) $value;
        }
    }

    public static function fromConfig(): self
    {
        /** @var array<string, array<string, mixed>> $values */
        $values = (array) config('legacy.values', []);

        return new self($values, array_values((array) config('courtwatch.supported_locales', ['sq', 'en'])));
    }

    public function bool(?string $raw): ?bool
    {
        $key = Text::key($raw);

        return $key === '' ? null : ($this->booleans[$key] ?? null);
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @param  string|null  $context  extra spellings checked first, e.g. "stage_outcome.plea_agreement"
     * @return T|null
     */
    public function enum(string $enum, ?string $raw, ?string $context = null): ?BackedEnum
    {
        $key = Text::key($raw);

        if ($key === '') {
            return null;
        }

        if ($context !== null) {
            foreach ((array) ($this->synonyms[$context] ?? []) as $synonym => $value) {
                if (Text::key((string) $synonym) === $key && ($case = $enum::tryFrom((string) $value)) !== null) {
                    return $case;
                }
            }
        }

        $value = $this->table($enum)[$key] ?? null;

        return $value === null ? null : $enum::tryFrom($value);
    }

    /**
     * Maps each part of a multi-valued field; parts that cannot be mapped are returned separately.
     *
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return array{0: list<string>, 1: list<string>} [mapped values, unmapped parts]
     */
    public function enums(string $enum, ?string $raw): array
    {
        $mapped = [];
        $unmapped = [];

        foreach (Text::split($raw) as $part) {
            $case = $this->enum($enum, $part);

            if ($case === null) {
                $unmapped[] = $part;
            } else {
                $mapped[] = (string) $case->value;
            }
        }

        return [array_values(array_unique($mapped)), $unmapped];
    }

    /**
     * A plausible calendar date as Y-m-d, or null.
     */
    public function date(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        $raw = trim($raw);

        foreach (self::DATE_FORMATS as $format) {
            // DateTimeImmutable returns false instead of throwing; the round trip rejects
            // overflowing dates such as 30.02 (which PHP would turn into 2 March).
            $date = DateTimeImmutable::createFromFormat('!'.$format, $raw);

            if ($date !== false && $date->format($format) === $raw) {
                $carbon = Carbon::instance($date);

                return $this->plausible($carbon) ? $carbon->toDateString() : null;
            }
        }

        return null;
    }

    /**
     * A whole number within the bounds ("45 min" → 45), or null.
     */
    public function int(?string $raw, int $min = 0, int $max = 1_000_000): ?int
    {
        if ($raw === null || ! preg_match('/-?\d+/', $raw, $match)) {
            return null;
        }

        $value = (int) $match[0];

        return $value < $min || $value > $max ? null : $value;
    }

    /**
     * An amount in euro in either number format ("1.250,50 €", "1,250.50", "2.500"), or null.
     */
    public function amount(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $number = (string) preg_replace('/[^0-9,.\-]/', '', $raw);

        if ($number === '' || ! preg_match('/\d/', $number)) {
            return null;
        }

        $lastComma = strrpos($number, ',');
        $lastDot = strrpos($number, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $number = str_replace([$thousands, $decimal], ['', '.'], $number);
        } elseif ($lastComma !== false) {
            $number = preg_match('/,\d{1,2}$/', $number) ? str_replace(',', '.', $number) : str_replace(',', '', $number);
        } elseif ($lastDot !== false && preg_match('/^\d{1,3}(\.\d{3})+$/', $number)) {
            $number = str_replace('.', '', $number);
        }

        if (! is_numeric($number)) {
            return null;
        }

        $value = round((float) $number, 2);

        return $value < 0 || $value > 999_999_999_999 ? null : $value;
    }

    private function plausible(Carbon $date): bool
    {
        return $date->year >= 1990 && $date->lte(now()->addYear());
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @return array<string, string>
     */
    private function table(string $enum): array
    {
        if (isset($this->enumTables[$enum])) {
            return $this->enumTables[$enum];
        }

        $name = Str::snake(class_basename($enum));
        $table = [];

        foreach ($enum::cases() as $case) {
            $value = (string) $case->value;
            $table[Text::key($value)] = $value;

            foreach ($this->locales as $locale) {
                $label = __('enums.'.$name.'.'.$value, [], $locale);

                if (is_string($label) && $label !== 'enums.'.$name.'.'.$value) {
                    $table[Text::key($label)] = $value;
                }
            }
        }

        foreach ((array) ($this->synonyms[$name] ?? []) as $key => $value) {
            if ($enum::tryFrom((string) $value) !== null) {
                $table[Text::key((string) $key)] = (string) $value;
            }
        }

        unset($table['']);

        return $this->enumTables[$enum] = $table;
    }
}
