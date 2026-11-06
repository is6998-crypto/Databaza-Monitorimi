<?php

namespace App\Models;

use App\Enums\PublicationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Report or publication shown on the public portal.
 *
 * @property int $id
 * @property string $slug
 * @property PublicationType $type
 * @property string $title_sq
 * @property string|null $title_en
 * @property string|null $summary_sq
 * @property string|null $summary_en
 * @property Carbon|null $published_on
 * @property bool $is_published
 * @property string|null $file_disk
 * @property string|null $file_path
 */
class Publication extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PublicationType::class,
            'published_on' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('publications')->logAll()->logExcept(['created_at', 'updated_at'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->whereNotNull('published_on')->where('published_on', '<=', now()->toDateString());
    }

    public function title(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->getAttribute("title_{$locale}") ?: $this->title_sq);
    }

    public function summary(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->getAttribute("summary_{$locale}") ?: $this->summary_sq;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
