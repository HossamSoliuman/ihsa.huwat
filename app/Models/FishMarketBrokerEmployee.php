<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The عمالة of a دلال, counted per وظيفة and جنسية rather than named one by one.
 * `job_title` and `nationality` hold the code of their option list, so a renamed option
 * leaves the record readable.
 */
class FishMarketBrokerEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fish_market_broker_id',
        'job_title',
        'nationality',
        'headcount',
    ];

    protected function casts(): array
    {
        return ['headcount' => 'integer'];
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(FishMarketBroker::class, 'fish_market_broker_id');
    }
}
