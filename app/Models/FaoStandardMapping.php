<?php

namespace App\Models;

class FaoStandardMapping extends MasterDataModel
{
    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];
}