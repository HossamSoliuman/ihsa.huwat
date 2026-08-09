<?php

namespace App\Models;

use Database\Factories\LoanInstalmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstalment extends Model
{
    /** @use HasFactory<LoanInstalmentFactory> */
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'instalment_number',
        'due_year',
        'due_month',
        'amount',
        'paid_amount',
        'payroll_run_id',
        'status',
    ];

    protected $attributes = ['paid_amount' => 0, 'status' => 'scheduled'];

    protected function casts(): array
    {
        return [
            'instalment_number' => 'integer',
            'due_year' => 'integer',
            'due_month' => 'integer',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }
}
