<?php

namespace App\Models;

class UserPermission extends BaseModel
{
    protected $casts = [
        'active' => 'boolean',
        'can_approve' => 'boolean',
        'can_export' => 'boolean',
    ];
}