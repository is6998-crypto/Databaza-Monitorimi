<?php

namespace App\Policies\Reference;

class CourtPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Court';
    }
}
