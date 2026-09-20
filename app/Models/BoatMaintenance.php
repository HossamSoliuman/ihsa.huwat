<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoatMaintenance extends BaseModel
{
    use HasFactory;

    public const STATUSES = ['معلقة', 'مكتملة', 'ملغاة'];

    protected $casts = [
        'date' => 'date',
    ];

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }
}
