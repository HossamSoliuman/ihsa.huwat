<?php

namespace App\Models;

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
}
