<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * دفعة من الدلال إلى مالك من صافي مبيعات مصيده. مستحق المالك عند الدلال =
 * مجموع owner_net في سطور بيع مصيده − مجموع هذه الدفعات.
 */
class DalalPayout extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }
}
