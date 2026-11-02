<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LegacyReviewItemPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'LegacyReviewItem';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
