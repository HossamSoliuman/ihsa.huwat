<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب مالك التعامل مع دلال ("طلبات المالكين"): عمولة الدلال % وأجور العمالة %
 * يقترحهما المالك ويقبلهما الدلال أو يرفضهما. المقبول منها يحدّد ما يُقتطع
 * من كل بيع لمصيد هذا المالك — انظر PartnershipService::termsFor.
 */
class DalalPartnership extends BaseModel
{
    use HasFactory;

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::PENDING => 'قيد الانتظار',
        self::ACCEPTED => 'مقبول',
        self::REJECTED => 'مرفوض',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::ACCEPTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
