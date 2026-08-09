<?php

namespace App\Models;

use Database\Factories\EmployeeLoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    /** @use HasFactory<EmployeeLoanFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'loan_number',
        'amount',
        'instalments_count',
        'instalment_amount',
        'first_instalment_month',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $attributes = ['status' => 'requested'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'instalments_count' => 'integer',
            'instalment_amount' => 'decimal:2',
            'first_instalment_month' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function instalments(): HasMany
    {
        return $this->hasMany(LoanInstalment::class, 'loan_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
