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

    public function fishers()
    {
        return $this->hasMany(Fisher::class);
    }

    public function statisticsOfficers()
    {
        return $this->hasMany(StatisticsOfficer::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class, 'departure_port_id');
    }
}