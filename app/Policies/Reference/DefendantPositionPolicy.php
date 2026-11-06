<?php

namespace App\Policies\Reference;

class DefendantPositionPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'DefendantPosition';
    }
}
