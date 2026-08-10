<?php

namespace App\Models;

use Database\Factories\PayrollRunEmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRunEmployee extends Model
{
    /** @use HasFactory<PayrollRunEmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'employee_number',
        'employee_name',
        'department_name',
        'job_title_name',
        'port_name',
        'contract_type',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'worked_days',
        'absent_days',
        'overtime_minutes',
        'status',
    ];

    protected $attributes = [
        'basic_salary' => 0,
        'total_earnings' => 0,
        'total_deductions' => 0,
        'net_salary' => 0,
        'worked_days' => 0,
        'absent_days' => 0,
        'overtime_minutes' => 0,
        'status' => 'ok',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'worked_days' => 'integer',
            'absent_days' => 'integer',
            'overtime_minutes' => 'integer',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }
}
