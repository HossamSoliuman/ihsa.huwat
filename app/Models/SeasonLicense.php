<?php

namespace App\Models;

class SeasonLicense extends MasterDataModel
{
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'quota_kg' => 'float',
        'used_kg' => 'float',
    ];
}