<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A دلال attached to a market, filed either as a فرد or as a منشأة. `entity_type` is the
 * branch that decides which fieldset is required, which is why it stays an enum instead
 * of becoming an option list.
 */
class FishMarketBroker extends Model
{
    use HasFactory;

    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_ESTABLISHMENT = 'establishment';

    public const ENTITY_TYPES = [self::TYPE_INDIVIDUAL, self::TYPE_ESTABLISHMENT];

    /** @var array<string, string> */
    public const ENTITY_TYPE_LABELS = [
        self::TYPE_INDIVIDUAL => 'فرد',
        self::TYPE_ESTABLISHMENT => 'منشأة',
    ];

    /**
     * Columns each branch owns, so the fields of the branch not chosen are cleared
     * rather than kept from whatever the other fieldset submitted.
     *
     * @var array<string, list<string>>
     */
    public const BRANCH_FIELDS = [
        self::TYPE_INDIVIDUAL => ['full_name', 'nationality', 'job_title'],
        self::TYPE_ESTABLISHMENT => ['entity_name', 'commercial_registration_no', 'email', 'tax_number', 'national_address'],
    ];

    protected $fillable = [
        'fish_market_id',
        'entity_type',
        'full_name',
        'nationality',
        'phone',
        'job_title',
        'entity_name',
        'commercial_registration_no',
        'email',
        'tax_number',
        'national_address',
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

    /** The name to print for this broker, whichever branch it was filed under. */
    public function displayName(): string
    {
        return $this->entity_type === self::TYPE_INDIVIDUAL
            ? (string) $this->full_name
            : (string) $this->entity_name;
    }

    public function entityTypeLabel(): string
    {
        return self::ENTITY_TYPE_LABELS[$this->entity_type] ?? $this->entity_type;
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('entity_name')->orderBy('full_name')->orderBy('id');
    }
}
