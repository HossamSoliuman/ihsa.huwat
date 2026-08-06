<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A family of the national catch coding sheet: the code ending in "00" that every
 * species beneath it rolls up to, such as 1500 Serranidae — أسماك الهامور.
 */
class FishFamily extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'scientific_name',
        'english_name',
        'local_name_gulf',
        'local_name_red_sea',
        'is_active',
    ];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['code' => 'integer', 'is_active' => 'boolean'];
    }

    public function species(): HasMany
    {
        return $this->hasMany(FishSpecies::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('code');
    }
}
