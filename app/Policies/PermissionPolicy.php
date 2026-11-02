<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps the standard policy abilities to Shield-style permission names ("Update:Court").
 * Record-level rules are added by subclasses.
 */
abstract class PermissionPolicy
{
    abstract protected function subject(): string;

    protected function allowed(User $user, string $affix): bool
    {
        return $user->checkPermissionTo($affix.':'.$this->subject());
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user, 'ViewAny');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allowed($user, 'View');
    }

    public function create(User $user): bool
    {
        return $this->allowed($user, 'Create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allowed($user, 'Update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allowed($user, 'Delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user, 'Delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allowed($user, 'Restore');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowed($user, 'Restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allowed($user, 'ForceDelete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowed($user, 'ForceDelete');
    }

    public function replicate(User $user, Model $model): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
