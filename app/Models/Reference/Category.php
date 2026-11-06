<?php

namespace App\Models\Reference;

class Category extends ReferenceModel
{
    protected $table = 'categories';

    public static function references(): array
    {
        return [
            ['criminal_offences', 'category_id'],
        ];
    }
}
