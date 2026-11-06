<?php

namespace App\Services\Research;

use App\Enums\ResearcherStatus;
use App\Models\ResearcherProfile;
use App\Models\User;
use App\Notifications\ResearcherReviewed;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Approves, rejects or suspends a researcher account. Rejecting or suspending revokes
 * every API token; the researcher is told the outcome by e-mail. Each decision is audited.
 */
final class ResearcherReview
{
    /**
     * @throws AuthorizationException
     */
    public function decide(ResearcherProfile $profile, ResearcherStatus $status, User $reviewer, ?string $note = null): ResearcherProfile
    {
        Gate::forUser($reviewer)->authorize('approve', $profile);

        if ($status === ResearcherStatus::Pending) {
            throw new \InvalidArgumentException('A review decision cannot set an account back to pending.');
        }

        DB::transaction(function () use ($profile, $status, $reviewer, $note): void {
            $profile->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => filled($note) ? $note : null,
            ]);

            if ($status !== ResearcherStatus::Approved) {
                $profile->user->tokens()->delete();
            }

            activity('researchers')
                ->causedBy($reviewer)
                ->performedOn($profile)
                ->event($status->value)
                ->withProperties(['researcher_id' => $profile->user_id, 'note' => $note])
                ->log('Researcher '.$status->value);
        });

        $profile->user->notify(new ResearcherReviewed($profile));

        return $profile;
    }
}
