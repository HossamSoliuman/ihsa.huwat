<?php

namespace App\Models;

class Species extends MasterDataModel
{
    protected $table = 'species';

    protected $casts = [
        'avg_weight_kg' => 'float',
        'avg_length_cm' => 'float',
        'catch_kg' => 'float',
        'trend' => 'float',
        'review_date' => 'date',
    ];
}