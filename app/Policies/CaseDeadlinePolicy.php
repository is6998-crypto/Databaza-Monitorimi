<?php

namespace App\Policies;

use App\Models\CaseDeadline;
use App\Models\CourtCase;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Legal time limits are read-only and visible to whoever may see the case they belong to.
 */
class CaseDeadlinePolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', CourtCase::class);
    }

    public function view(User $user, CaseDeadline $deadline): bool
    {
        return Gate::forUser($user)->allows('view', $deadline->case);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CaseDeadline $deadline): bool
    {
        return false;
    }

    public function delete(User $user, CaseDeadline $deadline): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
