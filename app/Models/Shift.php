<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'crosses_midnight',
        'grace_minutes',
        'is_active',
    ];

    protected $attributes = [
        'crosses_midnight' => false,
        'grace_minutes' => 15,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'crosses_midnight' => 'boolean',
            'grace_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }
}
