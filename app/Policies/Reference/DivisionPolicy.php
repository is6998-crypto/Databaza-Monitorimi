<?php

namespace App\Policies\Reference;

class DivisionPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Division';
    }
}
