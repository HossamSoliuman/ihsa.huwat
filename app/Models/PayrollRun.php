<?php

namespace App\Models;

use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'run_number',
        'period_year',
        'period_month',
        'period_start',
        'period_end',
        'payment_date',
        'employees_count',
        'total_earnings',
        'total_deductions',
        'net_total',
        'status',
        'payment_reference',
        'note',
        'created_by',
        'calculated_at',
        'approved_by',
        'approved_at',
        'paid_at',
        'closed_at',
    ];

    protected $attributes = [
        'employees_count' => 0,
        'total_earnings' => 0,
        'total_deductions' => 0,
        'net_total' => 0,
        'status' => self::STATUS_DRAFT,
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
            'employees_count' => 'integer',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_total' => 'decimal:2',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(PayrollRunEmployee::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PayrollRunIssue::class);
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
