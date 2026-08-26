<?php

namespace App\Models;

class Alert extends BaseModel
{
    protected $casts = [
        'date' => 'date',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
