<?php

namespace App\Models;

class DataQualityIssue extends BaseModel
{
    protected $casts = [
        'due_date' => 'date',
    ];
}