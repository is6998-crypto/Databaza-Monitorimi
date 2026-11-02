<?php

namespace App\Policies\Reference;

class CriminalOffencePolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'CriminalOffence';
    }
}
