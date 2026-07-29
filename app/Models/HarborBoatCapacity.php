<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarborBoatCapacity extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $fillable = ['port_id', 'boat_type', 'capacity', 'status'];

    protected $attributes = ['capacity' => 0, 'status' => 'available'];

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
