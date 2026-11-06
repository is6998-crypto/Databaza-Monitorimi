<?php

namespace App\Policies\Reference;

use App\Models\User;
use App\Policies\PermissionPolicy;
use App\Support\Permissions;

/**
 * Reference data: Editors manage it (create, update, merge, import, restore);
 * deleting is reserved to Super Admin; Analysts read; Monitors only use it in forms.
 */
abstract class ReferencePolicy extends PermissionPolicy
{
    public function merge(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::MERGE_REFERENCE) && $this->allowed($user, 'Update');
    }

    public function import(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::IMPORT_REFERENCE) && $this->allowed($user, 'Create');
    }
}
