<?php

namespace App\Models\Reference;

class DefendantPosition extends ReferenceModel
{
    protected $table = 'defendant_positions';

    public static function references(): array
    {
        return [
            ['case_parties', 'defendant_position_id'],
        ];
    }
}
