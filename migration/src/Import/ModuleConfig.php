<?php

namespace Migration\Import;

use App\Enums\CaseType;
use InvalidArgumentException;

/**
 * One legacy module (one flat legacy table) and how its columns map to the new schema.
 */
final class ModuleConfig
{
    /**
     * @param  array<string, string|null>  $columns  field => legacy column
     * @param  array<string, array<string, string|null>>  $stages  stage => [date|outcome|appellant => legacy column]
     * @param  array<string, string>  $defaults  field => code used when the row has no value
     */
    public function __construct(
        public readonly string $key,
        public readonly string $table,
        public readonly CaseType $caseType,
        public readonly string $label,
        public readonly array $columns,
        public readonly array $stages,
        public readonly array $defaults,
        public readonly string $idColumn = 'id',
    ) {}

    /**
     * Modules from config('legacy.modules'), optionally only the given keys.
     *
     * @param  list<string>  $only
     * @return array<string, self>
     */
    public static function all(array $only = []): array
    {
        /** @var array<string, array<string, mixed>> $modules */
        $modules = (array) config('legacy.modules', []);
        $unknown = array_diff($only, array_keys($modules));

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown legacy module(s): '.implode(', ', $unknown).'. Known: '.implode(', ', array_keys($modules)).'.');
        }

        $result = [];

        foreach ($modules as $key => $module) {
            if ($only !== [] && ! in_array($key, $only, true)) {
                continue;
            }

            $result[$key] = new self(
                key: $key,
                table: (string) $module['table'],
                caseType: CaseType::from((string) $module['case_type']),
                label: (string) ($module['label'] ?? $key),
                columns: array_map(fn (mixed $column): ?string => is_string($column) && $column !== '' ? $column : null, (array) ($module['columns'] ?? [])),
                stages: array_map(fn (mixed $parts): array => array_map(fn (mixed $column): ?string => is_string($column) && $column !== '' ? $column : null, (array) $parts), (array) ($module['stages'] ?? [])),
                defaults: array_map(fn (mixed $value): string => (string) $value, (array) ($module['defaults'] ?? [])),
                idColumn: (string) config('legacy.id_column', 'id'),
            );
        }

        return $result;
    }

    /**
     * Legacy column of a field ("court", or "stage.judgment_announced.date"), or null.
     */
    public function column(string $field): ?string
    {
        if (str_starts_with($field, 'stage.')) {
            [, $stage, $part] = array_pad(explode('.', $field, 3), 3, '');

            return $this->stages[$stage][$part] ?? null;
        }

        return $this->columns[$field] ?? null;
    }

    /**
     * Every legacy column this mapping reads.
     *
     * @return list<string>
     */
    public function mappedColumns(): array
    {
        // array_values first: the keys ("date", "outcome") repeat for every stage.
        $columns = array_values(array_filter($this->columns));

        foreach ($this->stages as $parts) {
            $columns = [...$columns, ...array_values(array_filter($parts))];
        }

        return array_values(array_unique([$this->idColumn, ...$columns]));
    }

    public function isCriminal(): bool
    {
        return $this->caseType->isCriminal();
    }
}
