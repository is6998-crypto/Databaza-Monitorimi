<?php

namespace App\Services\Reference;

use App\Models\Reference\ReferenceModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * "Merge duplicates" (proposal B5): re-points every foreign key that references the
 * duplicates to the record being kept, then soft-deletes the duplicates. Rows that
 * would violate a unique constraint after re-pointing are removed first.
 */
class ReferenceMerger
{
    /**
     * @param  Collection<int, ReferenceModel>|array<int, ReferenceModel>  $duplicates
     * @return array<string, int> number of re-pointed rows per "table.column"
     */
    public function merge(ReferenceModel $keep, Collection|array $duplicates, ?User $by = null): array
    {
        $duplicates = collect($duplicates)
            ->filter(fn (ReferenceModel $duplicate): bool => $duplicate->getKey() !== $keep->getKey());

        foreach ($duplicates as $duplicate) {
            if ($duplicate::class !== $keep::class) {
                throw new InvalidArgumentException('Only records of the same type can be merged.');
            }
        }

        if ($duplicates->isEmpty()) {
            return [];
        }

        $stats = [];

        DB::transaction(function () use ($keep, $duplicates, &$stats): void {
            foreach ($duplicates as $duplicate) {
                foreach ($keep::references() as $reference) {
                    [$table, $column] = $reference;
                    $uniqueWith = $reference[2] ?? [];
                    $key = "{$table}.{$column}";

                    if ($uniqueWith !== []) {
                        $this->dropConflicts($table, $column, $uniqueWith, (int) $keep->getKey(), (int) $duplicate->getKey());
                    }

                    $stats[$key] = ($stats[$key] ?? 0) + DB::table($table)
                        ->where($column, $duplicate->getKey())
                        ->update([$column => $keep->getKey()]);
                }

                DB::table('user_recent_values')
                    ->where('value_id', $duplicate->getKey())
                    ->whereIn('field', array_map(fn (array $reference): string => $reference[1], $keep::references()))
                    ->delete();

                $duplicate->forceFill(['is_active' => false])->save();
                $duplicate->delete();
            }
        });

        activity('reference')
            ->performedOn($keep)
            ->causedBy($by ?? auth()->user())
            ->event('merged')
            ->withProperties([
                'kept' => $keep->getKey(),
                'merged' => $duplicates->map(fn (ReferenceModel $duplicate) => $duplicate->getKey())->values()->all(),
                'repointed' => $stats,
            ])
            ->log('Merged duplicates into '.class_basename($keep).' #'.$keep->getKey());

        return $stats;
    }

    /**
     * Deletes rows of the duplicate that would collide with an existing row of the kept record.
     *
     * @param  list<string>  $uniqueWith
     */
    private function dropConflicts(string $table, string $column, array $uniqueWith, int $keepId, int $duplicateId): void
    {
        $query = DB::table($table.' as d')
            ->where("d.{$column}", $duplicateId)
            ->whereExists(function ($exists) use ($table, $column, $uniqueWith, $keepId): void {
                $exists->select(DB::raw(1))
                    ->from($table.' as k')
                    ->where("k.{$column}", $keepId);

                foreach ($uniqueWith as $other) {
                    $exists->whereColumn("k.{$other}", "d.{$other}");
                }
            });

        $conditions = [];

        foreach ($query->get(array_merge(["d.{$column}"], array_map(fn (string $other): string => "d.{$other}", $uniqueWith))) as $row) {
            $conditions[] = (array) $row;
        }

        foreach ($conditions as $condition) {
            DB::table($table)->where($condition)->delete();
        }
    }
}
