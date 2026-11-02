<?php

namespace App\Policies\Reference;

class ApplicantPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Applicant';
    }
}
