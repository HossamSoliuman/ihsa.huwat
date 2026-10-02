<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * موظف الإحصاء في الميناء — هو "العدّاد" في التطبيق. السجلّ سجلّ الوزارة
 * (الميناء، الرقم الوظيفي، الوردية، عدد الرحلات التي عدّها)، و`user_id`
 * حسابه إن كان يعمل من التطبيق أو من بوابته على /admin.
 *
 * عدّاد شركة التشغيل له `operating_company_id` (وطلب التوظيف الذي اعتُمد
 * منه)؛ عدّاد الوزارة شركته null ويديره المدير العام كما كان.
 */
class StatisticsOfficer extends BaseModel
{
    use HasFactory;

    public const ACTIVE = 'نشط';

    public const INACTIVE = 'غير نشط';

    public const SUSPENDED = 'موقوف';

    protected $casts = [
        'suspended_at' => 'datetime',
    ];

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(OperatingCompany::class, 'operating_company_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CounterApplication::class, 'counter_application_id');
    }

    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(CounterTransfer::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * أوقفه المدير العام (لا موظف الشركة) — فلا ترفع الشركة إيقافه.
     */
    public function isSuspendedByMinistry(): bool
    {
        return $this->isSuspended() && $this->suspender?->isSuperAdmin() === true;
    }

    /**
     * الرقم الوظيفي التالي لحساب لا رقم له — السجلات المبذورة تحمل SO-01…
     */
    public static function numberFor(User $user): string
    {
        return 'SO-'.str_pad((string) $user->id, 2, '0', STR_PAD_LEFT);
    }
}
