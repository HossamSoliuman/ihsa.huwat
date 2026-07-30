<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['name'];

    public function governorates(): HasMany
    {
        return $this->hasMany(Governorate::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
