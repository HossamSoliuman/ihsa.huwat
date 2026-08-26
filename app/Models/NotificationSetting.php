<?php

namespace App\Models;

class NotificationSetting extends BaseModel
{
    protected $casts = [
        'enabled' => 'boolean',
    ];
}
