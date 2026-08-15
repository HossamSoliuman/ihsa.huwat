<?php

namespace App\Models;

class Region extends MasterDataModel
{
    protected $casts = [
        'coast_length_km' => 'float',
        'total_catch_tons' => 'float',
    ];
}