<?php

namespace App\Policies\Reference;

class SecurityMeasureTypePolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'SecurityMeasureType';
    }
}
