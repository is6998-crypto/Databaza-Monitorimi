<?php

namespace App\Policies\Reference;

class ProsecutionOfficePolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'ProsecutionOffice';
    }
}
