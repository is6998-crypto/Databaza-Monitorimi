<?php

namespace App\Models\Reference;

class Applicant extends ReferenceModel
{
    protected $table = 'applicants';

    public static function references(): array
    {
        return [
            ['case_stages', 'applicant_id'],
        ];
    }
}
