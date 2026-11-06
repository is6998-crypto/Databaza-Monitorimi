<?php

namespace App\Policies;

use App\Models\CourtCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Parties, charges, stages, measures, sentences and type details are authorised through
 * their parent case.
 */
class CaseChildPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', CourtCase::class);
    }

    public function view(User $user, Model $model): bool
    {
        $case = $this->caseOf($model);

        return $case !== null && Gate::forUser($user)->allows('view', $case);
    }

    public function create(User $user): bool
    {
        return Gate::forUser($user)->allows('create', CourtCase::class);
    }

    public function update(User $user, Model $model): bool
    {
        $case = $this->caseOf($model);

        return $case !== null && Gate::forUser($user)->allows('update', $case);
    }

    public function delete(User $user, Model $model): bool
    {
        $case = $this->caseOf($model);

        return $case !== null && Gate::forUser($user)->allows('removeChildren', $case);
    }

    public function deleteAny(User $user): bool
    {
        return Gate::forUser($user)->allows('create', CourtCase::class);
    }

    private function caseOf(Model $model): ?CourtCase
    {
        $case = $model->getRelationValue('case');

        return $case instanceof CourtCase ? $case : null;
    }
}
