<?php

namespace App\Policies\Reference;

class DepartmentPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Department';
    }
}
