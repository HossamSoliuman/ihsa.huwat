<?php

namespace App\Models;

use Database\Factories\PayrollAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    /** @use HasFactory<PayrollAdjustmentFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CONSUMED = 'consumed';

    protected $fillable = [
        'employee_id',
        'salary_component_id',
        'adjustment_type',
        'period_year',
        'period_month',
        'amount',
        'reason',
        'status',
        'payroll_run_id',
        'created_by',
        'approved_by',
    ];

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected function casts(): array
    {
        return ['period_year' => 'integer', 'period_month' => 'integer', 'amount' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
