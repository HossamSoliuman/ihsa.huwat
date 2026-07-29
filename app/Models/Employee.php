<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'employment_application_id', 'employee_number', 'national_id',
        'job_title', 'department', 'job_grade', 'supervisor_name', 'supervisor_phone',
        'hire_date', 'contract_type', 'contract_end_date', 'base_salary', 'status',
    ];

    protected $attributes = ['contract_type' => 'permanent', 'base_salary' => 0, 'status' => 'active'];

    protected function casts(): array
    {
        return ['hire_date' => 'date', 'contract_end_date' => 'date', 'base_salary' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function employmentApplication(): BelongsTo
    {
        return $this->belongsTo(EmploymentApplication::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function assignedTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'assigned_employee_id');
    }
}
