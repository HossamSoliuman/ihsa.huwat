<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A species of the national catch coding sheet. `code` carries its identity — the
 * local names repeat across the coast, so كنعد alone lands on more than one code —
 * while `name_ar` stays the short name the dashboards and reports print.
 */
class FishSpecies extends Model
{
    use HasFactory;

    protected $fillable = [
        'fish_family_id',
        'code',
        'name_ar',
        'scientific_name',
        'english_name',
        'local_name_gulf',
        'local_name_red_sea',
        'sort_order',
        'is_active',
    ];

    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return ['code' => 'integer', 'sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(FishFamily::class, 'fish_family_id');
    }

    public function catchDetails(): HasMany
    {
        return $this->hasMany(CatchDetail::class, 'species_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Coded species first, in sheet order; anything added by hand follows by name. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('code is null')->orderBy('code')->orderBy('name_ar');
    }
}
