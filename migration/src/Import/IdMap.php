<?php

namespace Migration\Import;

use App\Models\LegacyIdMap;

/**
 * legacy key → new record id (table `legacy_id_map`). This is what makes the import
 * idempotent: anything already mapped is never created twice.
 */
final class IdMap
{
    /** @var array<string, int> */
    private array $cache = [];

    /** @var list<string> cache keys written since the last checkpoint */
    private array $pending = [];

    public function get(string $table, string $key, string $type): ?int
    {
        $cacheKey = $this->cacheKey($table, $key, $type);

        if (! array_key_exists($cacheKey, $this->cache)) {
            $id = LegacyIdMap::query()->where(['legacy_table' => $table, 'legacy_key' => $key, 'target_type' => $type])->value('target_id');

            if ($id === null) {
                return null;
            }

            $this->cache[$cacheKey] = (int) $id;
        }

        return $this->cache[$cacheKey];
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, int> legacy key => target id, for the keys that are mapped
     */
    public function many(string $table, array $keys, string $type): array
    {
        $found = [];

        foreach (array_chunk($keys, 1000) as $chunk) {
            $rows = LegacyIdMap::query()
                ->where(['legacy_table' => $table, 'target_type' => $type])
                ->whereIn('legacy_key', $chunk)
                ->pluck('target_id', 'legacy_key');

            foreach ($rows as $key => $id) {
                $found[(string) $key] = (int) $id;
                $this->cache[$this->cacheKey($table, (string) $key, $type)] = (int) $id;
            }
        }

        return $found;
    }

    public function put(string $table, string $key, string $type, int $id): void
    {
        LegacyIdMap::query()->updateOrCreate(
            ['legacy_table' => $table, 'legacy_key' => $key, 'target_type' => $type],
            ['target_id' => $id],
        );

        $cacheKey = $this->cacheKey($table, $key, $type);
        $this->cache[$cacheKey] = $id;
        $this->pending[] = $cacheKey;
    }

    /** Starts a unit of work (one case group). */
    public function checkpoint(): void
    {
        $this->pending = [];
    }

    /** The unit of work was rolled back: forget what it wrote. */
    public function rollback(): void
    {
        foreach ($this->pending as $cacheKey) {
            unset($this->cache[$cacheKey]);
        }

        $this->pending = [];
    }

    private function cacheKey(string $table, string $key, string $type): string
    {
        return $type.'|'.$table.'|'.$key;
    }
}
