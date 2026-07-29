<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Boat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'registration_no', 'boat_type', 'harbor_status', 'home_port_id'];

    protected $attributes = ['boat_type' => 'unclassified', 'harbor_status' => 'unclassified'];

    public function homePort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'home_port_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
