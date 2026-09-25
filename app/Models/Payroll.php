<?php

namespace App\Models;

use App\Models\Contracts\ExpenseSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * مسير رواتب شهر لقارب: أرقام الشهر التي حُسب منها نصيب الطاقم، وسطر لكل
 * فرد. الرواتب الثابتة تُرحَّل مصروفًا واحدًا على القارب بتاريخ آخر الشهر،
 * ومدفوعه يتبع سداد سطورها؛ الحصص توزيع للربح لا مصروف.
 */
class Payroll extends BaseModel implements ExpenseSource
{
    use HasFactory;

    public const MONTHS = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'revenue' => 'float',
        'expenses' => 'float',
        'depreciation' => 'float',
        'depreciation_charged' => 'float',
        'depreciation_deferred' => 'float',
        'net_profit' => 'float',
        'owner_share_percent' => 'float',
        'owner_share' => 'float',
        'crew_pool' => 'float',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function expense(): MorphOne
    {
        return $this->morphOne(Expense::class, 'source');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('year')->orderBy('month');
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
        return self::MONTHS[$this->month].' '.$this->year;
    }

    /**
     * الشهر بصيغة YYYY-MM — للمقارنة والترتيب.
     */
    public function getPeriodKeyAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        $lines = $this->relationLoaded('lines') ? $this->lines : $this->lines()->get();

        return $lines->isNotEmpty() && $lines->every(fn (PayrollLine $line) => $line->is_paid);
    }

    public function getHasPaymentsAttribute(): bool
    {
        return $this->relationLoaded('lines')
            ? $this->lines->contains(fn (PayrollLine $line) => $line->is_paid)
            : $this->lines()->whereNotNull('paid_at')->exists();
    }

    public function expensePosting(): ?array
    {
        $fixed = $this->lines()->with('payType')->get()->filter(fn (PayrollLine $line) => ! $line->payType?->isShare());
        $amount = round($fixed->sum(fn (PayrollLine $line) => $line->gross), 2);

        if ($amount <= 0) {
            return null;
        }

        return [
            'owner_id' => $this->owner_id,
            'category' => ExpenseCategory::CREW_SALARIES,
            'boat_id' => $this->boat_id,
            'date' => $this->period_end->toDateString(),
            'description' => "رواتب ثابتة — {$this->boat_name} — {$this->period_label} ({$this->payroll_number})",
            'amount' => $amount,
            // السلف صُرفت نقدًا من قبل، فالمسدَّد من الراتب = صافيه + سلفه.
            'paid_amount' => round($fixed->filter(fn (PayrollLine $line) => $line->is_paid)->sum(fn (PayrollLine $line) => $line->gross), 2),
        ];
    }

    public function expenseSourceLabel(): string
    {
        return 'مسير الرواتب';
    }

    public static function nextNumber(): string
    {
        $prefix = 'PAY-'.now()->year.'-';
        $last = static::where('payroll_number', 'like', $prefix.'%')->orderByDesc('payroll_number')->value('payroll_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
