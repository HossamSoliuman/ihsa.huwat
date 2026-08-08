<?php

namespace App\Models;

use App\Actions\Information\Dashboard\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A worker registered on a shop or an auction stall. `nationality` and `job_title` hold
 * the code of their option list, so a renamed option leaves the record readable.
 */
class FishMarketWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'fish_market_unit_id',
        'full_name',
        'national_id',
        'phone',
        'email',
        'nationality',
        'job_title',
    ];

    protected $hidden = ['national_id'];

    protected static function booted(): void
    {
        $forgetDashboard = static function (): void {
            DashboardCache::forget([
                DashboardCache::REGISTRY,
                DashboardCache::MARKET,
                DashboardCache::COMPLETENESS,
            ]);
        };

        static::saved($forgetDashboard);
        static::deleted($forgetDashboard);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(FishMarketUnit::class, 'fish_market_unit_id');
    }
}
