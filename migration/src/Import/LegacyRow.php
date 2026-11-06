<?php

namespace Migration\Import;

use BackedEnum;
use Migration\Support\Text;
use Migration\Support\ValueMapper;

/**
 * One flat legacy row with typed accessors. A non-empty value that cannot be mapped
 * returns null and is sent to review, never silently dropped.
 */
final class LegacyRow
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly ModuleConfig $module,
        public readonly array $data,
        private readonly ValueMapper $values,
        private readonly ReviewLog $review,
    ) {}

    public function id(): string
    {
        return (string) ($this->data[$this->module->idColumn] ?? '');
    }

    /**
     * The cleaned raw value of a mapped field, or null.
     */
    public function raw(string $field): ?string
    {
        $column = $this->module->column($field);

        return $column === null ? null : Text::clean($this->data[$column] ?? null);
    }

    public function has(string $field): bool
    {
        return $this->raw($field) !== null;
    }

    public function text(string $field, int $max = 0): ?string
    {
        $value = $this->raw($field);

        if ($value !== null && $max > 0 && mb_strlen($value) > $max) {
            $this->flag($field, ReviewLog::TOO_LONG, $value);

            return mb_substr($value, 0, $max);
        }

        return $value;
    }

    public function bool(string $field): ?bool
    {
        $raw = $this->raw($field);
        $value = $this->values->bool($raw);

        if ($raw !== null && $value === null) {
            $this->flag($field, ReviewLog::UNMAPPED_VALUE, $raw);
        }

        return $value;
    }

    public function date(string $field): ?string
    {
        $raw = $this->raw($field);
        $value = $this->values->date($raw);

        if ($raw !== null && $value === null) {
            $this->flag($field, ReviewLog::INVALID_DATE, $raw);
        }

        return $value;
    }

    public function int(string $field, int $min = 0, int $max = 1_000_000): ?int
    {
        $raw = $this->raw($field);
        $value = $this->values->int($raw, $min, $max);

        if ($raw !== null && $value === null) {
            $this->flag($field, ReviewLog::INVALID_NUMBER, $raw);
        }

        return $value;
    }

    public function amount(string $field): ?float
    {
        $raw = $this->raw($field);
        $value = $this->values->amount($raw);

        if ($raw !== null && $value === null) {
            $this->flag($field, ReviewLog::INVALID_NUMBER, $raw);
        }

        return $value;
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    public function enum(string $field, string $enum, ?string $context = null): ?BackedEnum
    {
        $raw = $this->raw($field);
        $value = $this->values->enum($enum, $raw, $context);

        if ($raw !== null && $value === null) {
            $this->flag($field, ReviewLog::UNMAPPED_VALUE, $raw);
        }

        return $value;
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @return list<string>
     */
    public function enums(string $field, string $enum): array
    {
        [$mapped, $unmapped] = $this->values->enums($enum, $this->raw($field));

        if ($unmapped !== []) {
            $this->flag($field, ReviewLog::UNMAPPED_VALUE, implode('; ', $unmapped));
        }

        return $mapped;
    }

    /**
     * Sends a value of this row to review.
     *
     * @param  array<string, mixed>  $payload
     */
    public function flag(string $field, string $reason, ?string $value, array $payload = [], ?bool $personal = null): void
    {
        $this->review->add($this->module, $this->id(), $field, $reason, $value, $payload, $personal);
    }

    /**
     * True when none of the mapped columns has a value.
     */
    public function isEmpty(): bool
    {
        foreach ($this->module->mappedColumns() as $column) {
            if ($column !== $this->module->idColumn && Text::clean($this->data[$column] ?? null) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Non-empty legacy columns that the mapping does not read (and that are not ignored).
     *
     * @return array<string, string>
     */
    public function unmappedColumns(): array
    {
        $known = [...$this->module->mappedColumns(), ...array_map('strval', (array) config('legacy.ignore_columns', []))];
        $unmapped = [];

        foreach ($this->data as $column => $value) {
            $clean = Text::clean($value);

            if ($clean !== null && ! in_array((string) $column, $known, true)) {
                $unmapped[(string) $column] = $clean;
            }
        }

        return $unmapped;
    }

    /**
     * Sort key: hearing date (rows without one first), then legacy id.
     */
    public function sortKey(): string
    {
        $date = $this->values->date($this->raw('hearing_date')) ?? '0000-00-00';

        return $date.'|'.str_pad($this->id(), 20, '0', STR_PAD_LEFT);
    }

    public function createdAt(): ?string
    {
        return $this->values->date(Text::clean($this->data['created_at'] ?? null));
    }
}
