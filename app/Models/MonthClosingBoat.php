<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * أرقام قارب في شهر مُغلق كما حسبها `CrewPool` لحظة الإغلاق، ومؤجَّل إهلاكه
 * الخارج الذي يدخل الشهر التالي. مستحقات طاقمه سطور مسيره.
 */
class MonthClosingBoat extends BaseModel
{
    protected $casts = [
        'revenue' => 'float',
        'expenses' => 'float',
        'depreciation_own' => 'float',
        'depreciation_brought_forward' => 'float',
        'depreciation' => 'float',
        'depreciation_charged' => 'float',
        'depreciation_deferred' => 'float',
        'net_profit' => 'float',
        'owner_share_percent' => 'float',
        'owner_share' => 'float',
        'crew_pool' => 'float',
        'assets' => 'array',
    ];

    public function closing(): BelongsTo
    {
        return $this->belongsTo(MonthClosing::class, 'month_closing_id');
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
