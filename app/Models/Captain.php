<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Captain extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['full_name', 'national_id', 'phone'];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
