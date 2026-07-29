<?php

namespace App\Models;

use Database\Factories\LeaveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    /** @use HasFactory<LeaveFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['employee_id', 'start_date', 'end_date', 'reason', 'status', 'approved_by'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
