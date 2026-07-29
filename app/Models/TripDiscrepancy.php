<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripDiscrepancy extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['trip_id', 'diff_kg', 'diff_percent', 'severity', 'reason', 'review_status', 'reviewed_by', 'reviewed_at'];

    protected $attributes = ['review_status' => 'pending'];

    protected function casts(): array
    {
        return ['diff_kg' => 'decimal:2', 'diff_percent' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('trip_id', Trip::query()->visibleTo($user)->select('id'));
    }
}
