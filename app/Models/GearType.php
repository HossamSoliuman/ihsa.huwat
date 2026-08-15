<?php

namespace App\Models;

class GearType extends MasterDataModel
{
    protected $casts = [
        'min_mesh_size_mm' => 'float',
        'selective' => 'boolean',
        'active' => 'boolean',
    ];
}