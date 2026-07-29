<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Port extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['governorate_id', 'name', 'location_name', 'location_url', 'is_active', 'latitude', 'longitude'];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'decimal:6', 'longitude' => 'decimal:6'];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role->code) {
            'region_manager' => $query->whereIn('governorate_id', Governorate::query()->where('region_id', $user->region_id)->select('id')),
            'gov_supervisor' => $query->where('governorate_id', $user->governorate_id),
            'port_supervisor' => $query->whereKey($user->port_id),
            default => $query,
        };
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(EmploymentJob::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function boats(): HasMany
    {
        return $this->hasMany(Boat::class, 'home_port_id');
    }

    public function capacities(): HasMany
    {
        return $this->hasMany(HarborBoatCapacity::class);
    }

    public function harborWorkers(): HasMany
    {
        return $this->hasMany(HarborWorker::class);
    }

    public function harborLicenses(): HasMany
    {
        return $this->hasMany(HarborLicense::class);
    }

    public function harborViolations(): HasMany
    {
        return $this->hasMany(HarborViolation::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }
}
