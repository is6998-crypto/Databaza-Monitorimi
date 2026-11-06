<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A private uploaded file (court document PDF, photo). Only served through a signed,
 * expiring URL after a policy check (App\Http\Controllers\AttachmentController).
 *
 * @property int $id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property string|null $description
 * @property int|null $uploaded_by
 * @property-read Model|null $attachable
 */
class Attachment extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('attachments')->logOnly(['original_name', 'mime_type', 'size', 'description'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function signedUrl(): string
    {
        return URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes((int) config('courtwatch.attachments.signed_url_minutes', 10)),
            ['attachment' => $this->id],
        );
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
