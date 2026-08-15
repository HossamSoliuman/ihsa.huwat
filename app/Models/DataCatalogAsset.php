<?php

namespace App\Models;

class DataCatalogAsset extends BaseModel
{
    protected $casts = [
        'source_of_truth' => 'boolean',
        'active' => 'boolean',
        'contains_pii' => 'boolean',
        'quality_controlled' => 'boolean',
    ];
}