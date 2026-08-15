<?php

namespace App\Models;

class Fisher extends BaseModel
{
    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }
}