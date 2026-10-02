<?php

namespace App\Models;

use Database\Factories\OperatingCompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * شركة تشغيل (شركة التشغيل): تشغّل موانئ يسندها إليها المدير العام، وتوظّف
 * العدّادين فيها — تفتح جولات التوظيف وتعتمد المتقدّمين وتوقفهم وتنقلهم.
 * يدخل بوابتها موظفوها بدور company (users.operating_company_id).
 */
class OperatingCompany extends BaseModel
{
    /** @use HasFactory<OperatingCompanyFactory> */
    use HasFactory;

    public const ACTIVE = 'active';

    public const SUSPENDED = 'suspended';

    public const STATUS_LABELS = [
        self::ACTIVE => 'نشطة',
        self::SUSPENDED => 'موقوفة',
    ];

    public function ports(): BelongsToMany
    {
        return $this->belongsToMany(Port::class, 'operating_company_ports')
            ->withPivot('started_at')
            ->withTimestamps();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(StatisticsOfficer::class);
    }

    public function hiringRounds(): HasMany
    {
        return $this->hasMany(HiringRound::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CounterApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function operatesPort(int $portId): bool
    {
        return $this->ports()->whereKey($portId)->exists();
    }
}
