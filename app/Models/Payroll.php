<?php

namespace App\Models;

use Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    /** @use HasFactory<PayrollFactory> */
    use HasFactory;

    protected $table = 'payroll';

    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'period_month', 'period_year', 'base_salary', 'allowances',
        'overtime_hours', 'overtime_amount', 'bonuses', 'deductions', 'net_salary',
        'paid_status', 'paid_at',
    ];

    protected $attributes = [
        'allowances' => 0, 'overtime_hours' => 0, 'overtime_amount' => 0,
        'bonuses' => 0, 'deductions' => 0, 'net_salary' => 0, 'paid_status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2', 'allowances' => 'decimal:2',
            'overtime_hours' => 'decimal:2', 'overtime_amount' => 'decimal:2',
            'bonuses' => 'decimal:2', 'deductions' => 'decimal:2',
            'net_salary' => 'decimal:2', 'paid_at' => 'datetime',
        ];
    }

    public function scopeForPeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
