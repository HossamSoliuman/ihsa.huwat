<?php

namespace App\Models;

use Database\Factories\EmployeeContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    /** @use HasFactory<EmployeeContractFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'contract_number',
        'contract_type',
        'start_date',
        'end_date',
        'probation_end_date',
        'working_hours_per_day',
        'working_days_per_week',
        'status',
        'note',
    ];

    protected $attributes = [
        'working_hours_per_day' => 8,
        'working_days_per_week' => 6,
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'working_hours_per_day' => 'decimal:2',
            'working_days_per_week' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
