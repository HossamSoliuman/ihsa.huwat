<?php

namespace App\Models;

class FaoStandardMapping extends BaseModel
{
    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];
}