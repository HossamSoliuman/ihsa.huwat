<?php

namespace App\Models;

class Boat extends BaseModel
{
    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}