<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * شهر مُغلق للمالك: لقطة مجمَّدة من أرقامه (مجموع القوارب + ما لا قارب له)
 * وسطر لكل قارب. ما دام الصف موجودًا فمصروفات الشهر ومسيراته مقفلة.
 */
class MonthClosing extends BaseModel
{
    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'revenue' => 'float',
        'boat_expenses' => 'float',
        'depreciation' => 'float',
        'depreciation_charged' => 'float',
        'depreciation_deferred' => 'float',
        'net_profit' => 'float',
        'owner_share' => 'float',
        'crew_pool' => 'float',
        'general_expenses' => 'float',
        'general_depreciation' => 'float',
        'general_assets' => 'array',
        'owner_net' => 'float',
        'closed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function boats(): HasMany
    {
        return $this->hasMany(MonthClosingBoat::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('year')->orderByDesc('month');
    }

    public function getPeriodStartAttribute(): CarbonImmutable
    {
        return CarbonImmutable::create($this->year, $this->month, 1)->startOfDay();
    }

    public function getPeriodEndAttribute(): CarbonImmutable
    {
        return $this->period_start->endOfMonth();
    }

    public function getPeriodLabelAttribute(): string
    {
        return self::label($this->year, $this->month);
    }

    public static function label(int $year, int $month): string
    {
        return Payroll::MONTHS[$month].' '.$year;
    }
}
