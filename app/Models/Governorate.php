<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['region_id', 'name', 'is_active'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Retiring a region retires everything under it, so the region's flag counts as much as its own. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereRelation('region', 'is_active', true);
    }

    /**
     * Desk order, following the status the row actually shows: live first, then the ones
     * stopped with their region, then the ones retired on their own — each group alphabetical.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_active')
            ->orderByDesc(Region::query()->select('is_active')->whereColumn('regions.id', 'governorates.region_id'))
            ->orderBy('name');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function ports(): HasMany
    {
        return $this->hasMany(Port::class);
    }
}
