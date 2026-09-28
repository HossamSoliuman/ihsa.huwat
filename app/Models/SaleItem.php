<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر فاتورة. في بيع الدلال يحمل رحلة المصيد ومالكه وما اقتُطع منه (عمولة
 * الدلال وأجور العمالة) وصافي المالك — الفاتورة الواحدة قد تجمع مصيد أكثر
 * من مالك بنِسب مختلفة. في بيع المالك: الرحلة رحلته والصافي كامل السطر.
 */
class SaleItem extends BaseModel
{
    use HasFactory;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * سطور مصيد المالك التي باعها دلال (البائع غيره) مضمومًا إليها `sales` —
     * أساس فواتير الدلال وحساباته وأدائه عند المالك (O5).
     */
    public function scopeSoldByDalalFor(Builder $query, User $owner): Builder
    {
        return $query
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.owner_id', $owner->id)
            ->where('sales.seller_id', '!=', $owner->id);
    }
}
