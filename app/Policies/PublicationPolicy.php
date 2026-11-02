<?php

namespace App\Policies;

class PublicationPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'Publication';
    }
}
