<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * القارب سجلّ الوزارة نفسه: يضيفه المالك من بوابته فيظهر في صفحة الميناء
 * ومركز المعلومات وعدّادات لوحة الحكومة. الأعمدة النصية القديمة (owner,
 * captain, boat_type) تُملأ من العلاقات عند الحفظ حتى تبقى صفحات الوزارة صادقة.
 */
class Boat extends BaseModel
{
    use HasFactory;

    public const STATUSES = ['نشط', 'غير نشط', 'صيانة', 'في البحر'];

    protected $casts = [
        'license_expiry' => 'date',
        'license_date' => 'date',
        'next_inspection_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Boat $boat) {
            if ($boat->isDirty('owner_id') && $boat->owner_id) {
                $boat->owner = User::find($boat->owner_id)?->name ?? $boat->owner;
            }
            if ($boat->isDirty('captain_id')) {
                $boat->captain = $boat->captain_id ? User::find($boat->captain_id)?->name : null;
            }
            if ($boat->isDirty('boat_type_id') && $boat->boat_type_id) {
                $boat->boat_type = BoatType::find($boat->boat_type_id)?->name ?? $boat->boat_type;
            }
        });
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function captainUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BoatCategory::class, 'boat_category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BoatType::class, 'boat_type_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function fishers(): HasMany
    {
        return $this->hasMany(Fisher::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(BoatMaintenance::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(BoatInspection::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(FleetDocument::class, 'documentable');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }
}
