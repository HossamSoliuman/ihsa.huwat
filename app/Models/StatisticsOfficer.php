<?php

namespace App\Models;

class StatisticsOfficer extends BaseModel
{
    public function port()
    {
        return $this->belongsTo(Port::class);
    }
}