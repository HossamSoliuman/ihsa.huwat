<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * فحص قارب (سلامة/صلاحية إبحار). آخر فحص للقارب هو "الحالي"، وموعده
 * القادم يُنسخ إلى `boats.next_inspection_date` — انظر InspectionService.
 */
class BoatInspection extends BaseModel
{
    use HasFactory;

    public const RESULTS = ['مطابق', 'مطابق بملاحظات', 'غير مطابق'];

    protected $casts = [
        'inspection_date' => 'date',
        'next_due_date' => 'date',
    ];

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->whereHas('boat', fn ($q) => $q->where('owner_id', $owner->id));
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}
