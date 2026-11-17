<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * The monitor's written account of one court session ("web report"): what happened, in prose. An editor reads
 * it, may edit it and comments on it. One report per hearing.
 *
 * The title and the text may name people, so both are encrypted at rest, never leave the staff panel and are not
 * copied into the audit log (the log records that the text was edited, by whom and when).
 *
 * @property int $id
 * @property int $hearing_id
 * @property string|null $title
 * @property string|null $body
 * @property ReportStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $reviewed_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Hearing $hearing
 * @property-read User|null $author
 * @property-read User|null $reviewer
 * @property-read Collection<int, HearingReportComment> $comments
 */
class HearingReport extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'status', 'created_at', 'updated_at', 'deleted_at', 'submitted_at', 'reviewed_at', 'reviewed_by'];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'title' => 'encrypted',
            'body' => 'encrypted',
            'status' => ReportStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if ($report->created_by === null && auth()->id() !== null) {
                $report->created_by = (int) auth()->id();
            }
        });

        static::saving(function (self $report): void {
            if (auth()->id() !== null) {
                $report->updated_by = (int) auth()->id();
            }
        });

        // The text is never written to the audit log; the fact that it changed is.
        static::updated(function (self $report): void {
            if ($report->wasChanged(['title', 'body'])) {
                activity('reports')
                    ->performedOn($report)
                    ->event('edited')
                    ->withProperties(['words' => $report->wordCount()])
                    ->log('Report text edited');
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('reports')
            ->logOnly(['hearing_id', 'status', 'reviewed_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Reports a user may see. A monitor (without a broader role) sees only the reports they wrote and the
     * reports of hearings they monitored.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isMonitorOnly()) {
            return;
        }

        $monitorId = $user->monitor?->id;

        $query->where(function (Builder $query) use ($user, $monitorId): void {
            $query->where('hearing_reports.created_by', $user->id);

            if ($monitorId !== null) {
                $query->orWhereHas('hearing', fn (Builder $hearing) => $hearing->where('monitor_id', $monitorId));
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), ReportStatus::Submitted->value);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id
            || ($user->monitor !== null && $this->hearing->monitor_id === $user->monitor->id);
    }

    /**
     * The text without markup (for the word count and the plain-text copy).
     */
    public function plainText(): string
    {
        $text = (string) preg_replace('/<\/(p|h[1-6]|li|blockquote|div)>|<br\s*\/?>/i', "\n", (string) $this->body);

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    }

    public function wordCount(): int
    {
        return Str::wordCount($this->plainText(), "ëçËÇ'’-0123456789");
    }

    /**
     * Staff label, e.g. "Raporti i seancës së 12.03.2026 — PKR.nr.12/24 · Gjykata Themelore e Prishtinës".
     */
    public function getDisplayLabelAttribute(): string
    {
        return (string) __('reports.title', [
            'date' => $this->hearing->hearing_date->format((string) config('courtwatch.date_format')),
            'case' => $this->hearing->case->display_label,
        ]);
    }

    // ---------------------------------------------------------------- relations

    /**
     * @return BelongsTo<Hearing, $this>
     */
    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<HearingReportComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(HearingReportComment::class)->oldest('id');
    }
}
