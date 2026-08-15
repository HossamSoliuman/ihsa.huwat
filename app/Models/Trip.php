<?php

namespace App\Models;

class Trip extends BaseModel
{
    protected $casts = [
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
    ];

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function departurePort()
    {
        return $this->belongsTo(Port::class, 'departure_port_id');
    }

    public function catchRecords()
    {
        return $this->hasMany(CatchRecord::class);
    }
}