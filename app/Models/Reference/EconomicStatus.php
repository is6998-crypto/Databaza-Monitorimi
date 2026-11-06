<?php

namespace App\Models\Reference;

class EconomicStatus extends ReferenceModel
{
    protected $table = 'economic_statuses';

    public static function references(): array
    {
        return [
            ['case_parties', 'economic_status_id'],
        ];
    }
}
