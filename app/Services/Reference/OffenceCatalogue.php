<?php

namespace App\Services\Reference;

use App\Models\Reference\Category;
use App\Models\Reference\CriminalCode;
use App\Models\Reference\CriminalOffence;
use App\Models\Reference\ReferenceModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

/**
 * The full list of criminal offences that can be picked on a charge, and the statistics categories they are
 * sorted into (the "Veprat penale" list taken from ejudiciary.org). The list lives in three CSV files under
 * database/data:
 *
 *   criminal-codes.csv       code, name_sq, name_en, year      (the codes and laws the articles belong to)
 *   offence-categories.csv   code, name_sq, name_en, replaces  (the categories used for statistics; "replaces" names
 *                                                              earlier categories, separated by |, merged into it)
 *   criminal-offences.csv    criminal_code, article_number, title_sq, title_en, category, is_active, source
 *
 * sync() brings the database in line with them and can be run any number of times: codes and categories are
 * matched by code, offences by code + article. Offences and categories that are not on the list are set
 * inactive (never deleted: charges may point at them), so the pickers and statistics offer only the list.
 */
class OffenceCatalogue
{
    private readonly ReferenceMerger $merger;

    public function __construct(private readonly ?string $directory = null, ?ReferenceMerger $merger = null)
    {
        $this->merger = $merger ?? new ReferenceMerger;
    }

    /**
     * @return array{codes: int, categories: int, offences: int, created: int, deactivated: int}
     */
    public function sync(): array
    {
        return DB::transaction(function (): array {
            $created = 0;

            $codes = [];
            foreach ($this->read('criminal-codes.csv') as $row) {
                $record = $this->upsert(CriminalCode::withTrashed()->where('code', $row['code']), CriminalCode::class, [
                    'code' => $row['code'],
                    'name_sq' => $row['name_sq'],
                    'name_en' => $row['name_en'] ?: null,
                    'year' => $row['year'] !== '' ? (int) $row['year'] : null,
                ], $created);
                $codes[$row['code']] = $record->id;
            }

            $categories = [];
            foreach ($this->read('offence-categories.csv') as $row) {
                // A category kept from before the list had codes is matched by its Albanian name.
                $query = Category::withTrashed()->where(fn ($query) => $query->where('code', $row['code'])
                    ->orWhere(fn ($query) => $query->whereNull('code')->whereRaw('lower(name_sq) = ?', [mb_strtolower($row['name_sq'])])));
                $record = $this->upsert($query, Category::class, [
                    'code' => $row['code'],
                    'name_sq' => $row['name_sq'],
                    'name_en' => $row['name_en'] ?: null,
                    'is_active' => true,
                ], $created);
                $categories[$row['code']] = $record->id;

                // Earlier categories with the same meaning are merged in (their offences re-pointed, then retired).
                $replaced = array_filter(array_map('trim', explode('|', $row['replaces'] ?? '')));
                if ($replaced !== []) {
                    $old = Category::query()->whereKeyNot($record->id)->whereIn(DB::raw('lower(name_sq)'), array_map('mb_strtolower', $replaced))->get();
                    $this->merger->merge($record, $old);
                }
            }

            $offences = [];
            foreach ($this->read('criminal-offences.csv') as $line => $row) {
                $codeId = $codes[$row['criminal_code']] ?? throw new RuntimeException("criminal-offences.csv line {$line}: unknown criminal code {$row['criminal_code']}.");
                $categoryId = $categories[$row['category']] ?? throw new RuntimeException("criminal-offences.csv line {$line}: unknown category {$row['category']}.");

                $record = $this->upsert(
                    CriminalOffence::withTrashed()->where('criminal_code_id', $codeId)->where('article_number', $row['article_number']),
                    CriminalOffence::class,
                    [
                        'criminal_code_id' => $codeId,
                        'article_number' => $row['article_number'],
                        'title_sq' => $row['title_sq'],
                        'title_en' => $row['title_en'] ?: null,
                        'category_id' => $categoryId,
                        'is_active' => $row['is_active'] !== '0',
                    ],
                    $created,
                );
                $offences[] = $record->id;
            }

            // Eloquent (not a bulk update) so every change lands in the audit log.
            $deactivated = 0;
            foreach ([
                CriminalOffence::query()->where('is_active', true)->whereNotIn('id', $offences),
                Category::query()->where('is_active', true)->whereNotIn('id', array_values($categories)),
            ] as $query) {
                foreach ($query->get() as $record) {
                    $record->forceFill(['is_active' => false])->save();
                    $deactivated++;
                }
            }

            return ['codes' => count($codes), 'categories' => count($categories), 'offences' => count($offences), 'created' => $created, 'deactivated' => $deactivated];
        });
    }

    /**
     * @template T of ReferenceModel
     *
     * @param  Builder<T>  $query
     * @param  class-string<T>  $model
     * @param  array<string, mixed>  $values
     * @return T
     */
    private function upsert($query, string $model, array $values, int &$created): ReferenceModel
    {
        $record = $query->orderBy('id')->first() ?? new $model;

        if (! $record->exists) {
            $values += ['is_active' => true];
            $created++;
        }

        $record->forceFill($values)->save();

        return $record;
    }

    /**
     * @return array<int, array<string, string>> line number => column => value
     */
    private function read(string $file): array
    {
        $path = ($this->directory ?? database_path('data')).'/'.$file;

        if (! is_file($path)) {
            throw new RuntimeException("Missing offence catalogue file {$path}.");
        }

        $csv = new SplFileObject($path);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $csv->setCsvControl(',', '"', '');

        $header = null;
        $rows = [];

        foreach ($csv as $index => $cells) {
            if (! is_array($cells) || $cells === [null]) {
                continue;
            }

            $cells = array_map(fn ($cell): string => trim((string) $cell), $cells);

            if ($header === null) {
                $header = $cells;

                continue;
            }

            $rows[(int) $index + 1] = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
        }

        return $rows;
    }
}
