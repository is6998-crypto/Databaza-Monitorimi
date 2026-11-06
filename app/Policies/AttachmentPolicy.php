<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Attachments inherit access from the case or hearing they belong to.
 */
class AttachmentPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Attachment';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var Attachment $model */
        $parent = $model->attachable;

        return $parent !== null
            && $this->allowed($user, 'View')
            && Gate::forUser($user)->allows('view', $parent);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Attachment $model */
        $parent = $model->attachable;

        return $parent !== null
            && $this->allowed($user, 'Update')
            && Gate::forUser($user)->allows('update', $parent);
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var Attachment $model */
        $parent = $model->attachable;

        return $parent !== null
            && Gate::forUser($user)->allows('update', $parent)
            && ($model->uploaded_by === $user->id || ! $user->isMonitorOnly());
    }
}
