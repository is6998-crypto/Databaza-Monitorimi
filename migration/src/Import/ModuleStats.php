<?php

namespace Migration\Import;

/**
 * Counters for one legacy module during one import run.
 */
final class ModuleStats
{
    public int $legacyRows = 0;

    public int $groups = 0;

    public int $rowsImported = 0;

    public int $rowsAlreadyImported = 0;

    public int $rowsEmpty = 0;

    public int $rowsFailed = 0;

    public int $rowsNotRead = 0;

    public int $groupsFailed = 0;

    /** @var array<string, int> record type => created this run */
    public array $created = [];

    public function __construct(public readonly ModuleConfig $module) {}

    public function created(string $type, int $count = 1): void
    {
        $this->created[$type] = ($this->created[$type] ?? 0) + $count;
    }

    /**
     * Every legacy row must be accounted for: imported, already imported, empty, failed
     * or (with --limit) not read.
     */
    public function accountedFor(): int
    {
        return $this->rowsImported + $this->rowsAlreadyImported + $this->rowsEmpty + $this->rowsFailed + $this->rowsNotRead;
    }

    public function balanced(): bool
    {
        return $this->accountedFor() === $this->legacyRows;
    }

    /**
     * @return array{rows: array{0: int, 1: int, 2: int}, created: array<string, int>}
     */
    public function snapshot(): array
    {
        return ['rows' => [$this->rowsImported, $this->rowsAlreadyImported, $this->rowsEmpty], 'created' => $this->created];
    }

    /**
     * Undo the counters of a group that was rolled back.
     *
     * @param  array{rows: array{0: int, 1: int, 2: int}, created: array<string, int>}  $snapshot
     */
    public function restore(array $snapshot): void
    {
        [$this->rowsImported, $this->rowsAlreadyImported, $this->rowsEmpty] = $snapshot['rows'];
        $this->created = $snapshot['created'];
    }
}
