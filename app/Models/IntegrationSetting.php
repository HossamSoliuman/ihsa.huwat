<?php

namespace App\Models;

class IntegrationSetting extends MasterDataModel
{
    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];
}