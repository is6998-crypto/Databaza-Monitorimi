<?php

namespace App\Models\Reference;

class Nationality extends ReferenceModel
{
    protected $table = 'nationalities';

    public static function references(): array
    {
        return [
            ['case_parties', 'nationality_id'],
        ];
    }
}
