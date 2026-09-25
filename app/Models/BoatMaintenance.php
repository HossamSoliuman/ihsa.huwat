<?php

namespace App\Models;

use App\Models\Contracts\ExpenseSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BoatMaintenance extends BaseModel implements ExpenseSource
{
    use HasFactory;

    public const STATUSES = ['معلقة', 'مكتملة', 'ملغاة'];

    public const COMPLETED = 'مكتملة';

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * المصروف المرحَّل من الصيانة حين تكتمل — انظر ExpenseService::syncSource.
     */
    public function expense(): MorphOne
    {
        return $this->morphOne(Expense::class, 'source');
    }

    /**
     * ما يُرحَّل مصروفًا: التكلفة الفعلية، وإلا المتوقعة.
     */
    public function getCostAttribute(): float
    {
        return (float) ($this->actual_cost ?? $this->estimated_cost ?? 0);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    /**
     * الصيانة المكتملة بتكلفة وحدها تُرحَّل.
     */
    public function expensePosting(): ?array
    {
        if ($this->status !== self::COMPLETED || $this->cost <= 0) {
            return null;
        }

        return [
            'owner_id' => $this->boat->owner_id,
            'category' => ExpenseCategory::BOAT_MAINTENANCE,
            'boat_id' => $this->boat_id,
            'date' => $this->date->toDateString(),
            'description' => ($this->maintenanceType?->name ?? 'صيانة').' — '.$this->boat->name,
            'amount' => $this->cost,
        ];
    }

    public function expenseSourceLabel(): string
    {
        return 'سجل صيانة';
    }
}
