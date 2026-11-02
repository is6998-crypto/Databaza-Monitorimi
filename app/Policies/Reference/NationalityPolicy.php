<?php

namespace App\Policies\Reference;

class NationalityPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Nationality';
    }
}
