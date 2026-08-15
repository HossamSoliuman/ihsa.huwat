<?php

namespace App\Models;

class Port extends BaseModel
{
    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function boats()
    {
        return $this->hasMany(Boat::class);
    }

    public function fishingSites()
    {
        return $this->hasMany(FishingSite::class);
    }
}