<?php

namespace App\Models\Reference;

class SecurityMeasureType extends ReferenceModel
{
    protected $table = 'security_measure_types';

    public static function references(): array
    {
        return [
            ['measures', 'security_measure_type_id'],
        ];
    }
}
