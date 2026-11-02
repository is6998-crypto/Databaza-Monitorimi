<?php

namespace App\Models\Concerns;

use App\Enums\WorkflowStatus;
use App\Models\User;
use App\Models\WorkflowTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Review workflow: draft → submitted → in_review → approved → published, plus returned.
 * Transitions are performed by App\Services\WorkflowService (which checks permissions).
 *
 * @property WorkflowStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $approved_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $published_at
 * @property Carbon|null $returned_at
 * @property string|null $return_comment
 */
trait HasWorkflow
{
    public static function bootHasWorkflow(): void
    {
        static::creating(function (self $model): void {
            $model->status ??= WorkflowStatus::Draft;

            if ($model->created_by === null && auth()->id() !== null) {
                $model->created_by = (int) auth()->id();
            }
        });

        static::saving(function (self $model): void {
            if (auth()->id() !== null) {
                $model->updated_by = (int) auth()->id();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function workflowCasts(): array
    {
        return [
            'status' => WorkflowStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return MorphMany<WorkflowTransition, $this>
     */
    public function transitions(): MorphMany
    {
        return $this->morphMany(WorkflowTransition::class, 'subject')->latest('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved or published records: the only data used by dashboards, the portal and researchers.
     *
     * @param  Builder<static>  $query
     */
    public function scopeVerified(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('status'), WorkflowStatus::verifiedValues());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('status'), [WorkflowStatus::Submitted->value, WorkflowStatus::InReview->value]);
    }

    /**
     * Attributes captured in the workflow snapshot (used for the reviewer's diff).
     *
     * @return array<string, mixed>
     */
    public function workflowSnapshot(): array
    {
        $attributes = $this->attributesToArray();

        foreach (['id', 'public_ref', 'status', 'created_at', 'updated_at', 'deleted_at', 'submitted_at', 'approved_at', 'approved_by', 'published_at', 'returned_at', 'return_comment', 'updated_by', 'created_by'] as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
