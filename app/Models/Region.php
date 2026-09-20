<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Region extends BaseModel
{
    use HasFactory;

    public function governorates()
    {
        return $this->hasMany(Governorate::class);
    }
}
