<?php

namespace App\Policies\Reference;

class CategoryPolicy extends ReferencePolicy
{
    protected function subject(): string
    {
        return 'Category';
    }
}
