<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * سند مصروف للمالك. المبالغ تحسبها ExpenseService وحدها:
 * الإجمالي = (المبلغ − الخصم) + الضريبة، والحالة تتبع المدفوع منه.
 */
class Expense extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'float',
        'discount_pct' => 'float',
        'discount' => 'float',
        'vat_rate' => 'float',
        'vat_amount' => 'float',
        'total' => 'float',
        'paid_amount' => 'float',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    /**
     * السجل الذي ولّد المصروف تلقائيًا (صيانة مكتملة…)، أو لا شيء لسند يدوي.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function getRemainingAttribute(): float
    {
        return round(max(0, $this->total - $this->paid_amount), 2);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->total > 0 && $this->paid_amount >= $this->total;
    }

    public function getIsAutomaticAttribute(): bool
    {
        return $this->source_type !== null;
    }

    /**
     * اسم السجل المولِّد ("سجل صيانة"، "معدات الصيد"…) أو null لسند يدوي.
     */
    public function getSourceLabelAttribute(): ?string
    {
        return $this->source instanceof Contracts\ExpenseSource ? $this->source->expenseSourceLabel() : null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public static function nextNumber(): string
    {
        $prefix = 'EXP-'.now()->year.'-';
        $last = static::where('expense_number', 'like', $prefix.'%')->orderByDesc('expense_number')->value('expense_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
