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

    /** A port is only offered while its governorate — and the region above it — are live too. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('governorate_id', Governorate::query()->selectable()->select('id'));
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

    /**
     * Desk order, following the status the row actually shows: live first, then the ones
     * stopped with the geography above them, then the ones retired on their own.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_active')
            ->orderByDesc(
                Governorate::query()
                    ->join('regions', 'regions.id', '=', 'governorates.region_id')
                    ->whereColumn('governorates.id', 'ports.governorate_id')
                    ->selectRaw('governorates.is_active and regions.is_active')
            )
            ->orderBy('name');
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

    public function informationSubmissions(): HasMany
    {
        return $this->hasMany(InformationSubmission::class);
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
