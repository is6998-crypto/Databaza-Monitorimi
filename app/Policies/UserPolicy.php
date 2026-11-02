<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'User';
    }

    public function delete(User $user, Model $model): bool
    {
        // Users are deactivated, never deleted from the panel (keeps the audit trail intact).
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    public function resetMultiFactor(User $user, User $target): bool
    {
        return $user->isSuperAdmin() && $user->id !== $target->id;
    }
}
