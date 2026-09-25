<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\URL;

/**
 * فاتورة بيع: البائع مالك (يبيع مصيد رحلته) أو دلال (يبيع من مخزونه).
 * العمولة والأجور وصافي المالك تُملأ في بيع الدلال؛ في بيع المالك الصافي = الإجمالي.
 */
class Sale extends BaseModel
{
    use HasFactory;

    public const IN_PROGRESS = 'قيد التنفيذ';

    public const COMPLETED = 'مكتمل';

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function scopeForSeller(Builder $query, User $seller): Builder
    {
        return $query->where('seller_id', $seller->id);
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->total - (float) $this->paid_amount, 2);
    }

    /**
     * رابط الفاتورة المطبوعة — موقّع فيُفتح من التطبيق ويُشارك بلا دخول.
     */
    public function invoiceUrl(): string
    {
        return URL::signedRoute('invoices.show', ['sale' => $this->id]);
    }

    /**
     * رقم الفاتورة بصيغة YY-MM-NNNNNN — تسلسل الشهر كما في النظام القديم.
     */
    public static function nextInvoiceNumber(): string
    {
        $prefix = now()->format('y-m').'-';
        $last = static::where('invoice_number', 'like', $prefix.'%')->orderByDesc('invoice_number')->value('invoice_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
