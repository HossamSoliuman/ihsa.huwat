<?php

namespace App\Models;

use Database\Factories\PayrollRunItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItem extends Model
{
    /** @use HasFactory<PayrollRunItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'payroll_run_employee_id',
        'salary_component_id',
        'item_type',
        'code',
        'label_ar',
        'quantity',
        'rate',
        'amount',
        'source_type',
        'source_id',
        'calculation_details',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'calculation_details' => 'array',
        ];
    }

    public function payrollRunEmployee(): BelongsTo
    {
        return $this->belongsTo(PayrollRunEmployee::class);
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }
}
