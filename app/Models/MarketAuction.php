<?php

namespace App\Models;

class MarketAuction extends BaseModel
{
    protected $casts = [
        'auction_date' => 'date',
    ];

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }
}