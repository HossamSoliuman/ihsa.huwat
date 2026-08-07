<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * محل بيع سمك or دكة حراج. Both carry the same commercial-entity fields and the same
 * عمالة block, so they live in one table and are told apart by `unit_type`.
 */
class FishMarketUnit extends Model
{
    use HasFactory;

    public const TYPE_SHOP = 'shop';

    public const TYPE_AUCTION_STALL = 'auction_stall';

    public const TYPES = [self::TYPE_SHOP, self::TYPE_AUCTION_STALL];

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_SHOP => 'محل بيع سمك',
        self::TYPE_AUCTION_STALL => 'دكة حراج',
    ];

    protected $fillable = [
        'fish_market_id',
        'unit_type',
        'label',
        'entity_name',
        'commercial_registration_no',
        'municipality_licence_no',
        'national_address',
        'tax_number',
        'is_active',
    ];

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(FishMarket::class, 'fish_market_id');
    }

    public function workers(): HasMany
    {
        return $this->hasMany(FishMarketWorker::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->unit_type] ?? $this->unit_type;
    }
}
