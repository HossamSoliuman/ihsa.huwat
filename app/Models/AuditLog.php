<?php

namespace App\Models;

class AuditLog extends MasterDataModel
{
    protected $casts = [
        'timestamp' => 'datetime',
    ];
}