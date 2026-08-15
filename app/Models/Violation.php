<?php

namespace App\Models;

class Violation extends BaseModel
{
    protected $casts = [
        'date' => 'date',
    ];

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }
}