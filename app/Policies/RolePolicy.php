<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Used by Filament Shield's role editor.
 */
class RolePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Role';
    }

    public function delete(User $user, Model $model): bool
    {
        // The five built-in roles from the proposal cannot be deleted.
        return $this->allowed($user, 'Delete')
            && ! in_array($model->getAttribute('name'), ['super_admin', 'editor', 'monitor', 'analyst', 'researcher'], true);
    }
}
