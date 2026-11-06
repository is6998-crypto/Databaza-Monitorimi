<?php

namespace App\Services\Reference;

use App\Models\Reference\ReferenceModel;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use SplFileObject;

/**
 * Bulk CSV import for reference tables (proposal B5).
 *
 * The first row is a header. Known columns are mapped to model attributes; rows are matched
 * to existing records by the configured key (e.g. "code" or "name_sq", case-insensitive) and
 * updated, otherwise created. Relations can be given by code or name via resolvers.
 */
class CsvImporter
{
    /**
     * @param  class-string<ReferenceModel>  $model
     * @param  array<string, array{rules?: list<mixed>|string, resolve?: Closure(string): (int|null), attribute?: string}>  $columns
     * @param  list<string>  $matchOn  attribute(s) used to find existing rows
     */
    public function __construct(
        private readonly string $model,
        private readonly array $columns,
        private readonly array $matchOn,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(string $path, ?User $by = null): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $rows = $this->read($path);

        DB::transaction(function () use ($rows, &$result): void {
            foreach ($rows as $line => $row) {
                $attributes = $this->mapRow($row, $line, $result['errors']);

                if ($attributes === null) {
                    $result['skipped']++;

                    continue;
                }

                $record = $this->findExisting($attributes) ?? new $this->model;
                $isNew = ! $record->exists;
                $record->fill($attributes);

                if ($isNew && ! array_key_exists('is_active', $attributes)) {
                    $record->setAttribute('is_active', true);
                }

                if ($record->trashed()) {
                    $record->restore();
                }

                if ($isNew || $record->isDirty()) {
                    $record->save();
                    $isNew ? $result['created']++ : $result['updated']++;
                }
            }
        });

        activity('reference')
            ->causedBy($by ?? auth()->user())
            ->event('imported')
            ->withProperties(['model' => class_basename($this->model), 'created' => $result['created'], 'updated' => $result['updated'], 'skipped' => $result['skipped']])
            ->log('CSV import into '.class_basename($this->model));

        return $result;
    }

    /**
     * @return list<string>
     */
    public function header(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @return array<int, array<string, string>> line number => [column => value]
     */
    private function read(string $path): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $firstLine = (string) $file->fgets();
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $file->rewind();
        $file->setCsvControl($delimiter, '"', '\\');

        $header = null;
        $rows = [];

        foreach ($file as $index => $values) {
            if (! is_array($values) || $values === [null]) {
                continue;
            }

            $values = array_map(fn ($value): string => trim((string) $value), $values);

            if ($header === null) {
                $values[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', $values[0]);
                $header = array_map(fn (string $column): string => strtolower($column), $values);

                if (array_diff($this->requiredColumns(), $header) !== []) {
                    throw new RuntimeException(__('reference.import.missing_columns', ['columns' => implode(', ', array_diff($this->requiredColumns(), $header))]));
                }

                continue;
            }

            $rows[(int) $index + 1] = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function requiredColumns(): array
    {
        return array_values(array_filter(array_keys($this->columns), function (string $column): bool {
            $rules = $this->columns[$column]['rules'] ?? [];

            return in_array('required', is_array($rules) ? $rules : explode('|', $rules), true);
        }));
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $errors
     * @return array<string, mixed>|null
     */
    private function mapRow(array $row, int $line, array &$errors): ?array
    {
        $data = array_intersect_key($row, $this->columns);
        $rules = array_map(fn (array $column) => $column['rules'] ?? ['nullable'], $this->columns);
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors[] = $this->message('reference.import.row_error', ['line' => $line, 'message' => $validator->errors()->first()]);

            return null;
        }

        $attributes = [];

        foreach ($this->columns as $column => $definition) {
            $value = $data[$column] ?? '';

            if (isset($definition['resolve'])) {
                if ($value === '') {
                    continue;
                }

                $resolved = ($definition['resolve'])($value);

                if ($resolved === null) {
                    $errors[] = $this->message('reference.import.unknown_reference', ['line' => $line, 'column' => $column, 'value' => $value]);

                    return null;
                }

                $attributes[$definition['attribute'] ?? $column] = $resolved;

                continue;
            }

            if ($column === 'is_active') {
                if ($value !== '') {
                    $attributes['is_active'] = in_array(strtolower($value), ['1', 'true', 'yes', 'po', 'po/yes', 'active', 'aktiv'], true);
                }

                continue;
            }

            $attributes[$definition['attribute'] ?? $column] = $value === '' ? null : $value;
        }

        return $attributes;
    }

    /**
     * @param  array<string, string|int>  $replace
     */
    private function message(string $key, array $replace): string
    {
        $message = __($key, $replace);

        return is_string($message) ? $message : $key;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function findExisting(array $attributes): ?ReferenceModel
    {
        $query = $this->model::withTrashed();

        foreach ($this->matchOn as $attribute) {
            $value = $attributes[$attribute] ?? null;

            if ($value === null) {
                return null;
            }

            is_string($value)
                ? $query->whereRaw('lower(trim('.$attribute.')) = ?', [mb_strtolower(trim($value))])
                : $query->where($attribute, $value);
        }

        /** @var ReferenceModel|null $record */
        $record = $query->first();

        return $record;
    }
}
