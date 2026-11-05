<?php

namespace Migration\Import;

use Migration\Support\Text;

/**
 * Groups flat legacy rows (one per hearing and defendant) into cases.
 *
 * Rows of the same court that share any case number (court number, prosecution
 * number, …) belong to the same case, even when early rows only carry the
 * prosecution number and later rows both (union–find over the numbers).
 */
final class CaseGrouper
{
    /** @var array<string, string> row id => parent row id */
    private array $parent = [];

    /** @var array<string, string> number key => first row id with it */
    private array $owners = [];

    /**
     * @param  array<string, string|null>  $numbers  field => raw case number
     */
    public function add(string $rowId, string $courtKey, array $numbers): void
    {
        $this->parent[$rowId] ??= $rowId;

        foreach ($numbers as $field => $number) {
            $key = Text::caseNumberKey($number);

            // Ignore placeholders such as "0", "-" or "PA NR" that would merge unrelated cases.
            if (strlen($key) < 3 || ! preg_match('/\d/', $key)) {
                continue;
            }

            $ownerKey = $field.'|'.$courtKey.'|'.$key;

            if (isset($this->owners[$ownerKey])) {
                $this->union($rowId, $this->owners[$ownerKey]);
            } else {
                $this->owners[$ownerKey] = $rowId;
            }
        }
    }

    /**
     * Groups of row ids, each sorted, ordered by their first row id.
     *
     * @return list<list<string>>
     */
    public function groups(): array
    {
        $groups = [];

        foreach (array_keys($this->parent) as $rowId) {
            $groups[$this->find((string) $rowId)][] = (string) $rowId;
        }

        $groups = array_map(function (array $ids): array {
            usort($ids, fn (string $a, string $b): int => self::compareIds($a, $b));

            return $ids;
        }, array_values($groups));

        usort($groups, fn (array $a, array $b): int => self::compareIds($a[0], $b[0]));

        return $groups;
    }

    public static function compareIds(string $a, string $b): int
    {
        return is_numeric($a) && is_numeric($b) ? ((int) $a <=> (int) $b) : strcmp($a, $b);
    }

    private function find(string $rowId): string
    {
        $root = $rowId;

        while ($this->parent[$root] !== $root) {
            $root = $this->parent[$root];
        }

        // Path compression.
        while ($this->parent[$rowId] !== $root) {
            $next = $this->parent[$rowId];
            $this->parent[$rowId] = $root;
            $rowId = $next;
        }

        return $root;
    }

    private function union(string $a, string $b): void
    {
        $rootA = $this->find($a);
        $rootB = $this->find($b);

        if ($rootA !== $rootB) {
            // Keep the smallest row id as the root, so group keys are stable between runs.
            if (self::compareIds($rootA, $rootB) < 0) {
                $this->parent[$rootB] = $rootA;
            } else {
                $this->parent[$rootA] = $rootB;
            }
        }
    }
}
