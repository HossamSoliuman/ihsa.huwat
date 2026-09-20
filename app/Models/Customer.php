<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * عميل في قائمة حساب (مالك أو دلال) — يُباع له المصيد.
 */
class Customer extends BaseModel
{
    use HasFactory;

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeForAccount(Builder $query, User $account): Builder
    {
        return $query->where('account_user_id', $account->id);
    }
}
