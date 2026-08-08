<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['name', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Live regions first, the retired ones sinking to the bottom, each group alphabetical. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_active')->orderBy('name');
    }

    public function governorates(): HasMany
    {
        return $this->hasMany(Governorate::class);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }
}
