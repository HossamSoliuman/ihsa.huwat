<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Governorate extends BaseModel
{
    use HasFactory;

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
