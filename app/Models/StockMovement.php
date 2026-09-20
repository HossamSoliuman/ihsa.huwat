<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * سطر في دفتر المخزون — انظر App\Services\Stock\StockLedger الذي يكتبه.
 */
class StockMovement extends BaseModel
{
    use HasFactory;

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(StockMovementType::class, 'stock_movement_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForHolder(Builder $query, User $holder): Builder
    {
        return $query->where('holder_id', $holder->id);
    }
}
