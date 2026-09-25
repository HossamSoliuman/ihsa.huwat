<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * ملف الدلال التجاري: الهوية والموقع والدكة والسجل التجاري والضريبي والشركة
 * وشعارها — منه تُطبع ترويسة فواتيره. سجلّ واحد لكل حساب دلال، يُنشأ عند
 * أول قراءة (انظر forUser) فلا يحتاج حساب الدلال خطوة إعداد مستقلة.
 */
class DalalProfile extends BaseModel
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public static function forUser(User $dalal): self
    {
        return static::firstOrCreate(['user_id' => $dalal->id]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * الاسم على الفاتورة: اسم الشركة إن وُجد وإلا اسم الدلال.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: ($this->user?->name ?? '');
    }
}
