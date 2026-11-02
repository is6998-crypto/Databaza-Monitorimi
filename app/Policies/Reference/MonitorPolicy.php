<?php

namespace App\Policies\Reference;

class MonitorPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Monitor';
    }
}
