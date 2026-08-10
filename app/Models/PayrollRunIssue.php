<?php

namespace App\Models;

use Database\Factories\PayrollRunIssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunIssue extends Model
{
    /** @use HasFactory<PayrollRunIssueFactory> */
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'level',
        'code',
        'message_ar',
        'resolved',
    ];

    protected $attributes = ['resolved' => false];

    protected function casts(): array
    {
        return ['resolved' => 'boolean'];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
