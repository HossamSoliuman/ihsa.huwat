<?php

namespace App\Models;

class FishingSeason extends MasterDataModel
{
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'min_size_cm' => 'float',
        'quota_tons' => 'float',
    ];
}