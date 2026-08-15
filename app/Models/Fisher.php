<?php

namespace App\Models;

class Fisher extends MasterDataModel
{
    protected $casts = [
        'license_expiry' => 'date',
    ];
}