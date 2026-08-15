<?php

namespace App\Models;

class FishingSite extends BaseModel
{
    public function port()
    {
        return $this->belongsTo(Port::class);
    }
}