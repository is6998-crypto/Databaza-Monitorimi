<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Base class for admin-managed reference data: soft-deletable, bilingual (Serbian-ready),
 * with an is_active flag, an audit trail and a "merge duplicates" contract.
 *
 * @property int $id
 * @property bool $is_active
 * @property string|null $legacy_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static Builder<static> active()
 */
abstract class ReferenceModel extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Column prefix of the translated label (name_sq, name_en, name_sr).
     * Person tables (judges, prosecutors) override labelColumn() instead.
     */
    public static function labelPrefix(): string
    {
        return 'name';
    }

    public static function isTranslatable(): bool
    {
        return true;
    }

    /**
     * Columns shown as the record label and used for search/sorting.
     */
    public static function labelColumn(): string
    {
        return static::isTranslatable() ? static::labelPrefix().'_sq' : 'name';
    }

    /**
     * Foreign keys that point at this table, for the merge-duplicates action.
     * Each entry: [table, column, unique-with columns (to drop conflicting rows)].
     *
     * @return list<array{0: string, 1: string, 2?: list<string>}>
     */
    abstract public static function references(): array;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('reference')
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where($this->qualifyColumn('is_active'), true);
    }

    /**
     * Label in the current locale, falling back to Albanian.
     */
    public function label(?string $locale = null): string
    {
        if (! static::isTranslatable()) {
            return (string) $this->getAttribute('name');
        }

        $locale ??= app()->getLocale();
        $prefix = static::labelPrefix();

        return (string) ($this->getAttribute("{$prefix}_{$locale}") ?: $this->getAttribute("{$prefix}_sq"));
    }

    public function getLabelAttribute(): string
    {
        return $this->label();
    }

    /**
     * Options for selects: id => label in the current locale (active rows only by default).
     *
     * @return array<int, string>
     */
    public static function selectOptions(bool $onlyActive = true): array
    {
        $query = static::query()->orderBy(static::labelColumn());

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get()->mapWithKeys(fn (self $model): array => [$model->id => $model->label()])->all();
    }
}
