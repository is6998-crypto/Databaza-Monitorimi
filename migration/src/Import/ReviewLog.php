<?php

namespace Migration\Import;

use App\Models\LegacyReviewItem;
use Illuminate\Support\Str;

/**
 * Writes what the importer could not map to `legacy_review_items` instead of dropping it.
 *
 * Personal values (names, free text) never go into the plain `value` column: they are
 * kept only in the encrypted `payload`.
 */
final class ReviewLog
{
    public const UNKNOWN_REFERENCE = 'unknown_reference';

    public const NEW_REFERENCE = 'new_reference_value';

    public const UNMAPPED_VALUE = 'unmapped_value';

    public const INVALID_DATE = 'invalid_date';

    public const INVALID_NUMBER = 'invalid_number';

    public const TOO_LONG = 'too_long';

    public const CONFLICT = 'conflicting_values';

    public const INCONSISTENT = 'inconsistent_answers';

    public const CHRONOLOGY = 'stage_chronology';

    public const UNMAPPED_COLUMNS = 'unmapped_columns';

    public const EMPTY_ROW = 'empty_row';

    public const GROUP_FAILED = 'import_failed';

    /** Fields whose raw values can contain personal data. */
    public const PERSONAL_FIELDS = [
        'party_name', 'injured_party_name', 'plaintiff_name', 'respondent_name',
        'judge', 'prosecutor', 'monitor', 'notes', 'summary', 'security_comment', 'first_instance_decision',
    ];

    /** @var array<string, array<string, int>> module => reason => count, this run */
    private array $counts = [];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function add(ModuleConfig $module, string $legacyId, string $field, string $reason, ?string $value, array $payload = [], ?bool $personal = null): void
    {
        $personal ??= in_array($field, self::PERSONAL_FIELDS, true);

        $item = LegacyReviewItem::query()->firstOrNew([
            'legacy_table' => $module->table,
            'legacy_id' => $legacyId,
            'field' => Str::limit($field, 60, ''),
            'reason' => $reason,
        ]);

        if (! $item->exists) {
            $this->counts[$module->key][$reason] = ($this->counts[$module->key][$reason] ?? 0) + 1;
        }

        $item->fill([
            'legacy_module' => $module->key,
            'value' => $personal || $value === null ? null : Str::limit($value, 500),
            'payload' => ['value' => $value, ...$payload],
        ]);
        $item->save();
    }

    /**
     * @return array<string, int> reason => count, for one module, this run
     */
    public function counts(string $module): array
    {
        return $this->counts[$module] ?? [];
    }

    /**
     * Forgets the counts recorded since $snapshot (the group failed and was rolled back).
     *
     * @param  array<string, array<string, int>>  $snapshot
     */
    public function restore(array $snapshot): void
    {
        $this->counts = $snapshot;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function snapshot(): array
    {
        return $this->counts;
    }
}
