<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const VERIFIED_STATUSES = ['approved', 'closed'];

    protected $fillable = [
        'trip_code', 'boat_id', 'captain_id', 'port_id', 'assigned_employee_id',
        'expected_arrival', 'actual_arrival', 'captain_reported_weight',
        'verified_weight', 'status', 'counting_started_at', 'counting_ended_at',
        'approved_by', 'approved_at', 'edited_after_approval',
    ];

    protected $attributes = ['status' => 'expected', 'edited_after_approval' => false];

    protected function casts(): array
    {
        return [
            'expected_arrival' => 'datetime', 'actual_arrival' => 'datetime',
            'counting_started_at' => 'datetime', 'counting_ended_at' => 'datetime',
            'approved_at' => 'datetime', 'edited_after_approval' => 'boolean',
            'captain_reported_weight' => 'decimal:2', 'verified_weight' => 'decimal:2',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role->code) {
            'port_supervisor' => $query->where('port_id', $user->port_id),
            'gov_supervisor' => $query->whereHas('port', fn (Builder $query) => $query->where('governorate_id', $user->governorate_id)),
            'stat_employee' => $query->whereHas('assignedEmployee', fn (Builder $query) => $query->where('user_id', $user->id)),
            default => $query,
        };
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(TripDiscrepancy::class);
    }

    public function catchDetails(): HasMany
    {
        return $this->hasMany(CatchDetail::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TripAttachment::class);
    }
}
