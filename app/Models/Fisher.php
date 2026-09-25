<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * الصياد سجلّ الوزارة (الهوية والرخصة والميناء). الكابتن له فوقه حساب دخول
 * (`user_id`) لأنه يعمل من التطبيق؛ الطاقم سجلّ فقط. `role` النصي يبقى
 * لصفحات الوزارة ويُملأ من `fisher_role_id`.
 */
class Fisher extends BaseModel
{
    use HasFactory;

    public const CAPTAIN_ROLE = 'قبطان';

    protected $casts = [
        'license_expiry' => 'date',
        'fixed_salary' => 'float',
        'profit_shares' => 'float',
        'custom_share_percent' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Fisher $fisher) {
            if ($fisher->isDirty('fisher_role_id') && $fisher->fisher_role_id) {
                $fisher->role = FisherRole::find($fisher->fisher_role_id)?->name ?? $fisher->role;
            }
        });
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fisherRole(): BelongsTo
    {
        return $this->belongsTo(FisherRole::class);
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(FleetDocument::class, 'documentable');
    }

    public function payType(): BelongsTo
    {
        return $this->belongsTo(PayType::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(CrewAdvance::class);
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function getIsCaptainAttribute(): bool
    {
        return $this->user_id !== null;
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeCaptains(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }
}
