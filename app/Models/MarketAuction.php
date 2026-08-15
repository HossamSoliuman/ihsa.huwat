<?php

namespace App\Models;

class MarketAuction extends MasterDataModel
{
    protected $casts = [
        'date' => 'date',
        'offered_kg' => 'float',
        'sold_kg' => 'float',
        'min_price' => 'float',
        'max_price' => 'float',
        'avg_price' => 'float',
    ];
}