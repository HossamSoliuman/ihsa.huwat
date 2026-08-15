<?php

namespace App\Models;

class SeasonLicense extends BaseModel
{
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function fishingSeason()
    {
        return $this->belongsTo(FishingSeason::class);
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function port()
    {
        return $this->belongsTo(Port::class);
    }
}