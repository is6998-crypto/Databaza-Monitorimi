<?php

namespace App\Services;

use App\Enums\WorkflowStatus;
use App\Models\CaseParty;
use App\Models\CourtCase;
use App\Models\Hearing;
use App\Models\User;
use App\Models\WorkflowTransition;
use App\Notifications\WorkflowNotification;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Review workflow (proposal B5/B6.5): draft → submitted → in_review → approved → published,
 * plus returned (with a mandatory comment). Every transition is authorised, recorded with a
 * snapshot (for the reviewer's diff), audited and notified.
 */
class WorkflowService
{
    public function submit(CourtCase|Hearing $record, User $user): void
    {
        $this->transition($record, WorkflowStatus::Submitted, $user);
    }

    public function startReview(CourtCase|Hearing $record, User $user): void
    {
        $this->transition($record, WorkflowStatus::InReview, $user);
    }

    public function approve(CourtCase|Hearing $record, User $user, ?string $comment = null): void
    {
        $this->transition($record, WorkflowStatus::Approved, $user, $comment);
    }

    public function returnWithComment(CourtCase|Hearing $record, User $user, string $comment): void
    {
        $this->transition($record, WorkflowStatus::Returned, $user, $comment);
    }

    public function publish(CourtCase|Hearing $record, User $user): void
    {
        $this->transition($record, WorkflowStatus::Published, $user);
    }

    public function unpublish(CourtCase|Hearing $record, User $user): void
    {
        $this->transition($record, WorkflowStatus::Approved, $user);
    }

    public function transition(CourtCase|Hearing $record, WorkflowStatus $to, User $user, ?string $comment = null): void
    {
        $from = $record->status;

        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('workflow.errors.invalid', ['from' => $from->getLabel(), 'to' => $to->getLabel()])]);
        }

        Gate::forUser($user)->authorize($this->abilityFor($from, $to), $record);

        if ($to === WorkflowStatus::Returned && blank($comment)) {
            throw ValidationException::withMessages(['comment' => __('workflow.errors.comment_required')]);
        }

        DB::transaction(function () use ($record, $from, $to, $user, $comment): void {
            $record->status = $to;
            $now = now();

            match ($to) {
                WorkflowStatus::Submitted => $record->forceFill(['submitted_at' => $now, 'return_comment' => null]),
                WorkflowStatus::Approved => $from === WorkflowStatus::Published
                    ? $record->forceFill(['published_at' => null])
                    : $record->forceFill(['approved_at' => $now, 'approved_by' => $user->id]),
                WorkflowStatus::Published => $record->forceFill(['published_at' => $now]),
                WorkflowStatus::Returned => $record->forceFill(['returned_at' => $now, 'return_comment' => $comment]),
                default => null,
            };

            $record->save();

            WorkflowTransition::query()->create([
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $user->id,
                'comment' => $comment,
                'snapshot' => in_array($to, [WorkflowStatus::Submitted, WorkflowStatus::Approved], true) ? $this->snapshot($record) : null,
                'created_at' => $now,
            ]);

            activity('workflow')
                ->performedOn($record)
                ->causedBy($user)
                ->event('transition')
                ->withProperties(['from' => $from->value, 'to' => $to->value, 'comment' => $comment])
                ->log("{$from->value} → {$to->value}");
        });

        $this->notify($record, $from, $to, $user, $comment);
    }

    /**
     * Policy ability required for a transition.
     */
    public function abilityFor(WorkflowStatus $from, WorkflowStatus $to): string
    {
        return match (true) {
            $to === WorkflowStatus::Submitted => 'submit',
            $to === WorkflowStatus::Published => 'publish',
            $from === WorkflowStatus::Published => 'unpublish',
            default => 'review',
        };
    }

    /**
     * Plain-array snapshot of the record (and, for cases, everything inside it).
     * Party names are never stored; their blind-index hash is, so a name change still shows up.
     *
     * @return array<string, mixed>
     */
    public function snapshot(CourtCase|Hearing $record): array
    {
        $record = $record->fresh() ?? $record;

        if ($record instanceof Hearing) {
            return [
                ...$record->workflowSnapshot(),
                'present_parties' => $record->presentParties()->pluck('case_parties.id')->sort()->values()->all(),
            ];
        }

        $record->loadMissing(['parties', 'charges', 'stages', 'measures', 'sentences', 'administrativeDetails', 'civilDetails']);

        $rows = fn ($collection, array $except = []): array => $collection
            ->mapWithKeys(fn (Model $model): array => [$model->getKey() => Arr::except($model->attributesToArray(), ['id', 'case_id', 'created_at', 'updated_at', 'deleted_at', ...$except])])
            ->all();

        return [
            ...$record->workflowSnapshot(),
            'parties' => $record->parties->mapWithKeys(fn (CaseParty $party): array => [$party->id => [
                ...Arr::except($party->attributesToArray(), ['id', 'case_id', 'name', 'name_tokens', 'created_at', 'updated_at', 'deleted_at']),
            ]])->all(),
            'charges' => $rows($record->charges),
            'stages' => $rows($record->stages),
            'measures' => $rows($record->measures),
            'sentences' => $rows($record->sentences),
            'administrative' => $record->administrativeDetails ? Arr::except($record->administrativeDetails->attributesToArray(), ['id', 'case_id', 'created_at', 'updated_at']) : null,
            'civil' => $record->civilDetails ? Arr::except($record->civilDetails->attributesToArray(), ['id', 'case_id', 'created_at', 'updated_at']) : null,
        ];
    }

    /**
     * Changes since the previous reviewed version: compares the live record with the snapshot
     * taken at the last approval (or, after a return, the previous submission).
     *
     * @return array{base: string|null, changes: list<array{field: string, old: mixed, new: mixed}>}
     */
    public function diff(CourtCase|Hearing $record): array
    {
        $latestSubmission = $record->transitions()->where('to_status', WorkflowStatus::Submitted->value)->first();

        $base = $record->transitions()
            ->whereIn('to_status', [WorkflowStatus::Approved->value, WorkflowStatus::Submitted->value])
            ->whereNotNull('snapshot')
            ->when($latestSubmission, fn ($query) => $query->where('id', '<', $latestSubmission->id))
            ->first();

        $old = $base !== null ? Arr::dot((array) $base->snapshot) : [];
        $new = Arr::dot($this->snapshot($record));
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $before = $old[$key] ?? null;
            $after = $new[$key] ?? null;

            if ($before !== $after && ! ($before === [] && $after === null) && ! ($before === null && $after === [])) {
                $changes[] = ['field' => (string) $key, 'old' => $before, 'new' => $after];
            }
        }

        return ['base' => $base?->to_status->value, 'changes' => $changes];
    }

    private function notify(CourtCase|Hearing $record, WorkflowStatus $from, WorkflowStatus $to, User $actor, ?string $comment): void
    {
        if ($to === WorkflowStatus::Submitted) {
            $reviewers = User::query()
                ->where('is_active', true)
                ->whereKeyNot($actor->id)
                ->get()
                ->filter(fn (User $user): bool => $user->checkPermissionTo(Permissions::REVIEW));

            Notification::send($reviewers, new WorkflowNotification($record, $to, $actor, $comment));

            return;
        }

        if (in_array($to, [WorkflowStatus::Returned, WorkflowStatus::Approved, WorkflowStatus::Published], true) && $from !== WorkflowStatus::Published) {
            $author = $record->created_by ? User::query()->find($record->created_by) : null;

            if ($author !== null && $author->id !== $actor->id && $author->is_active) {
                $author->notify(new WorkflowNotification($record, $to, $actor, $comment));
            }
        }
    }
}
