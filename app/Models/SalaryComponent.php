<?php

namespace App\Models;

use Database\Factories\SalaryComponentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    /** @use HasFactory<SalaryComponentFactory> */
    use HasFactory;

    public const TYPE_EARNING = 'earning';

    public const TYPE_DEDUCTION = 'deduction';

    public const CALCULATION_FIXED = 'fixed';

    public const CALCULATION_PERCENT_OF_BASIC = 'percent_of_basic';

    protected $fillable = [
        'code',
        'name_ar',
        'component_type',
        'calculation_type',
        'is_basic',
        'sort_order',
        'is_active',
    ];

    protected $attributes = [
        'is_basic' => false,
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_basic' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function employeeSalaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }
}
