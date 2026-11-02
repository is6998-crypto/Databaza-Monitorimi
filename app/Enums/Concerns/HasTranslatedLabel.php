<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Translated labels for backed enums: lang/{locale}/enums.php → [EnumName => [value => label]].
 */
trait HasTranslatedLabel
{
    public function getLabel(): string
    {
        $key = 'enums.'.Str::snake(class_basename(static::class)).'.'.$this->value;
        $label = __($key);

        return $label === $key ? Str::headline($this->value) : $label;
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * @return array<string, string> value => label, for selects and checkbox lists.
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
