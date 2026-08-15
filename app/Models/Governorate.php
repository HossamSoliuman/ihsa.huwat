<?php

namespace App\Models;

class Governorate extends MasterDataModel
{
    protected $casts = [
        'coastal' => 'boolean',
        'total_catch_tons' => 'float',
    ];
}