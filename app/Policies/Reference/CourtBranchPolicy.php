<?php

namespace App\Policies\Reference;

class CourtBranchPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'CourtBranch';
    }
}
