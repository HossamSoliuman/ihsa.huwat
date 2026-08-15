<?php

namespace App\Models;

class DataCatalogAsset extends MasterDataModel
{
    protected $casts = [
        'source_of_truth' => 'boolean',
        'contains_pii' => 'boolean',
        'quality_controlled' => 'boolean',
        'active' => 'boolean',
    ];
}