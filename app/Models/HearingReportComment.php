<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A comment on a hearing report, from the editor or from the monitor who wrote it. Encrypted at rest, like the
 * report; the audit log records that a comment was added, not its text.
 *
 * @property int $id
 * @property int $hearing_report_id
 * @property int|null $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property-read HearingReport $report
 * @property-read User|null $user
 */
class HearingReportComment extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<HearingReport, $this>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(HearingReport::class, 'hearing_report_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
