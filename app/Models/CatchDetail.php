<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatchDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['trip_id', 'species_id', 'captain_reported_kg', 'verified_kg', 'boxes_count', 'is_unreported_by_captain', 'scale_photo_path'];

    protected function casts(): array
    {
        return ['captain_reported_kg' => 'decimal:2', 'verified_kg' => 'decimal:2', 'is_unreported_by_captain' => 'boolean'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'species_id');
    }
}
