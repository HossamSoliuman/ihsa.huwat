<?php

namespace App\Models;

class DataQualityIssue extends MasterDataModel
{
    protected $casts = [
        'due_date' => 'date',
    ];
}