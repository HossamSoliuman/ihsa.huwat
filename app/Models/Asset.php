<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * أصل يملكه المالك (قارب، محرك، معدات…) يُهلَك بالقسط الثابت شهريًا —
 * الحساب في App\Services\Owner\AssetDepreciation.
 */
class Asset extends BaseModel
{
    use HasFactory;

    public const ACTIVE = 'نشط';

    public const SOLD = 'مباع';

    public const DAMAGED = 'تالف';

    public const STATUSES = [self::ACTIVE, self::SOLD, self::DAMAGED];

    protected $casts = [
        'purchase_date' => 'date',
        'disposed_at' => 'date',
        'purchase_cost' => 'float',
        'salvage_value' => 'float',
        'disposal_value' => 'float',
        'useful_life_years' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }
}
