<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    public const PAYROLL_NONE = 'none';

    public const PAYROLL_UNPAID_DEDUCTION = 'unpaid_deduction';

    protected $fillable = [
        'code',
        'name_ar',
        'is_paid',
        'annual_days',
        'payroll_effect',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_paid' => true,
        'payroll_effect' => self::PAYROLL_NONE,
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'annual_days' => 'decimal:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
