<?php

namespace App\Policies\Reference;

class EconomicStatusPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'EconomicStatus';
    }
}
