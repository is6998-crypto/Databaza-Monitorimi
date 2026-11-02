<?php

namespace App\Policies\Reference;

class CriminalCodePolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'CriminalCode';
    }
}
