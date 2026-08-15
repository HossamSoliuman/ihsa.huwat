<?php

namespace App\Models;

class Boat extends MasterDataModel
{
    protected $casts = [
        'length_m' => 'float',
        'total_catch_kg' => 'float',
        'license_expiry' => 'date',
    ];
}