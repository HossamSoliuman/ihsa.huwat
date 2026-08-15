<?php

namespace App\Models;

class Region extends BaseModel
{
    public function governorates()
    {
        return $this->hasMany(Governorate::class);
    }
}