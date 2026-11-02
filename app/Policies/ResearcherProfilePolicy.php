<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

class ResearcherProfilePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'ResearcherProfile';
    }

    public function create(User $user): bool
    {
        // Researchers register themselves in /research.
        return false;
    }

    public function approve(User $user): bool
    {
        return $user->checkPermissionTo(Permissions::APPROVE_RESEARCHERS);
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
