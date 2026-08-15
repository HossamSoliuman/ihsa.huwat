<?php

namespace App\Models;

class Market extends BaseModel
{
    public function auctions()
    {
        return $this->hasMany(MarketAuction::class);
    }
}