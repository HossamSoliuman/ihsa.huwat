<?php

namespace App\Models;

class UserPermission extends MasterDataModel
{
    protected $casts = [
        'can_approve' => 'boolean',
        'can_export' => 'boolean',
        'active' => 'boolean',
    ];
}