<?php

namespace App\Models;

use App\Models\Contracts\ExpenseSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * معدات صيد يملكها المالك (شباك، قراقير، خيوط…) — نوعها من أنواع الأدوات
 * الوزارية، ومواسمها من مواسم الصيد الوزارية. تكلفة شرائها تُرحَّل مصروفًا.
 */
class FishingEquipment extends BaseModel implements ExpenseSource
{
    use HasFactory;

    public const CONDITIONS = ['جيدة', 'تحتاج صيانة', 'تالفة'];

    protected $table = 'fishing_equipment';

    protected $casts = [
        'purchase_date' => 'date',
        'unit_cost' => 'float',
        'quantity' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function gearType(): BelongsTo
    {
        return $this->belongsTo(GearType::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(FishingSeason::class, 'fishing_equipment_season')->withTimestamps();
    }

    public function expense(): MorphOne
    {
        return $this->morphOne(Expense::class, 'source');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function getTotalCostAttribute(): float
    {
        return round($this->unit_cost * $this->quantity, 2);
    }

    /**
     * مسموحة الآن: أحد مواسمها مفتوح هذا الشهر. بلا مواسم = غير مقيّدة.
     */
    public function getInSeasonAttribute(): ?bool
    {
        return $this->seasons->isEmpty() ? null : $this->seasons->contains(fn (FishingSeason $s) => $s->isOpenNow());
    }

    public function expensePosting(): ?array
    {
        if ($this->total_cost <= 0) {
            return null;
        }

        return [
            'owner_id' => $this->owner_id,
            'category' => ExpenseCategory::FISHING_EQUIPMENT,
            'boat_id' => $this->boat_id,
            'vendor_id' => $this->vendor_id,
            'date' => ($this->purchase_date ?? $this->created_at ?? now())->toDateString(),
            'description' => "شراء معدات: {$this->name} × {$this->quantity}",
            'amount' => $this->total_cost,
        ];
    }

    public function expenseSourceLabel(): string
    {
        return 'معدات الصيد';
    }
}
