<?php

namespace App\Models;

use App\Actions\Information\Dashboard\Support\DashboardCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * A fish market together with the investor holding it. المنطقة is read through the
 * governorate rather than stored, so a governorate that moves region takes its markets
 * with it.
 */
class FishMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'governorate_id',
        'name',
        'investor_name',
        'investor_commercial_registration_no',
        'investor_phone',
        'investor_email',
        'investor_tax_number',
        'investor_national_address',
        'is_active',
    ];

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        $forgetDashboard = static function (): void {
            DashboardCache::forget([
                DashboardCache::REGISTRY,
                DashboardCache::GEOGRAPHY,
                DashboardCache::MARKET,
                DashboardCache::COMPLETENESS,
            ]);
        };

        static::saved($forgetDashboard);
        static::deleted($forgetDashboard);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(FishMarketUnit::class);
    }

    public function shops(): HasMany
    {
        return $this->units()->where('unit_type', FishMarketUnit::TYPE_SHOP);
    }

    public function auctionStalls(): HasMany
    {
        return $this->units()->where('unit_type', FishMarketUnit::TYPE_AUCTION_STALL);
    }

    public function brokers(): HasMany
    {
        return $this->hasMany(FishMarketBroker::class);
    }

    /** Everyone working the market, whichever shop or stall they are registered on. */
    public function workers(): HasManyThrough
    {
        return $this->hasManyThrough(
            FishMarketWorker::class,
            FishMarketUnit::class,
            'fish_market_id',
            'fish_market_unit_id',
        );
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name')->orderBy('id');
    }
}
