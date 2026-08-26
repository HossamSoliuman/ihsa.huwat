<?php

namespace App\Models;

class StaffNotification extends BaseModel
{
    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(OrgStaff::class, 'org_staff_id');
    }
}
