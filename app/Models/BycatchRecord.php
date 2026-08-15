<?php

namespace App\Models;

class BycatchRecord extends BaseModel
{
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}