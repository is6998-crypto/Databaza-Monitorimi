<?php

namespace App\Policies\Reference;

class ProsecutorPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Prosecutor';
    }
}
