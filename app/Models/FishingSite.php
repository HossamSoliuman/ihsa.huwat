<?php

namespace App\Models;

class FishingSite extends MasterDataModel
{
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'depth_m' => 'float',
        'catch_kg' => 'float',
        'avg_catch_per_trip' => 'float',
    ];
}