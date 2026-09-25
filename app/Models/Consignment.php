<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * إرسال مصيد رحلة إلى مخزون دلال: يخرج من دفتر المالك ويدخل دفتر الدلال
 * بالوزن نفسه، والدلال يبيعه لاحقًا من بوابته.
 */
class Consignment extends BaseModel
{
    use HasFactory;

    public const SENT = 'مرسلة';

    public const RECEIVED = 'مستلمة';

    public const CANCELLED = 'ملغاة';

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }

    public static function nextNumber(): string
    {
        $prefix = 'CN-'.now()->year.'-';
        $last = static::where('consignment_number', 'like', $prefix.'%')->orderByDesc('consignment_number')->value('consignment_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
