<?php

namespace App\Models;

class Alert extends BaseModel
{
    protected $casts = [
        'date' => 'date',
    ];
}