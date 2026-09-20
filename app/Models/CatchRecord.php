<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر مصيد لصنف في رحلة: `captain_kg` ما أعلنه الكابتن، `counted_kg` ما
 * عدّه العدّاد، و`quantity_kg` القيمة المعتمدة التي تقرؤها صفحات الإحصاء.
 */
class CatchRecord extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'recorded_at' => 'date',
        'verified' => 'boolean',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
