<?php

namespace App\Models;

class Governorate extends BaseModel
{
    protected $casts = [
        'coastal' => 'boolean',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function ports()
    {
        return $this->hasMany(Port::class);
    }
}