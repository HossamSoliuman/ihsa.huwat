<?php

namespace App\Models;

class CatchRecord extends BaseModel
{
    protected $casts = [
        'recorded_at' => 'date',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }
}