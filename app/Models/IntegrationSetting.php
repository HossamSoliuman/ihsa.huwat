<?php

namespace App\Models;

class IntegrationSetting extends BaseModel
{
    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];
}