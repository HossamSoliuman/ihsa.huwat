<?php

namespace App\Models;

class Port extends MasterDataModel
{
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'total_catch_tons' => 'float',
    ];
}