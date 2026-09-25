<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر فرد في مسير: إعداد أجره وقت الحساب، أساسه (راتبه الثابت أو حصته)،
 * الزيادة والخصم، السلف المخصومة، والصافي. بعد السداد لا يُعاد حسابه.
 */
class PayrollLine extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'is_captain' => 'boolean',
        'fixed_salary' => 'float',
        'profit_shares' => 'float',
        'custom_share_percent' => 'float',
        'base_amount' => 'float',
        'bonus' => 'float',
        'deduction' => 'float',
        'advances' => 'float',
        'net' => 'float',
        'paid_amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function fisher(): BelongsTo
    {
        return $this->belongsTo(Fisher::class);
    }

    public function payType(): BelongsTo
    {
        return $this->belongsTo(PayType::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * المستحق قبل السلف: الأساس + الزيادة − الخصم.
     */
    public function getGrossAttribute(): float
    {
        return round($this->base_amount + $this->bonus - $this->deduction, 2);
    }
}
