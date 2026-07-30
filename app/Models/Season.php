<?php

namespace App\Models;

use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'status',
        'region_id',
        'start_date',
        'end_date',
        'fishing_tools',
        'licenses_count',
        'minimum_size',
        'maximum_size',
        'restrictions',
    ];

    protected $attributes = [
        'status' => self::STATUS_UPCOMING,
        'licenses_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fishing_tools' => 'array',
            'licenses_count' => 'integer',
            'minimum_size' => 'decimal:2',
            'maximum_size' => 'decimal:2',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
