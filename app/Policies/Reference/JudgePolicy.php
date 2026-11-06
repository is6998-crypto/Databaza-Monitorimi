<?php

namespace App\Policies\Reference;

class JudgePolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Judge';
    }
}
