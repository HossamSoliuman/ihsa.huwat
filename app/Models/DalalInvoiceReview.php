<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * مراجعة المالك لفاتورة دلال: سطور مصيده في بيعٍ واحد من مخزون الدلال.
 * تُنشأ قيد المراجعة مع البيع (سطر لكل مالك في الفاتورة)، فيقبلها المالك أو
 * يرفضها بسبب، ويردّ الدلال على المرفوضة فتعود قيد المراجعة. المراجعة لا
 * تغيّر الأرقام — المستحق يبقى صافي السطور حتى تُصحَّح بالتواصل.
 */
class DalalInvoiceReview extends BaseModel
{
    use HasFactory;

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::PENDING => 'قيد المراجعة',
        self::ACCEPTED => 'مقبولة',
        self::REJECTED => 'مرفوضة',
    ];

    public const STATUS_BADGES = [
        self::PENDING => 'badge-warn',
        self::ACCEPTED => 'badge-ok',
        self::REJECTED => 'badge-danger',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::REJECTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? '';
    }
}
