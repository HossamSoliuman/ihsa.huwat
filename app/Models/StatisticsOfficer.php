<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موظف الإحصاء في الميناء — هو "العدّاد" في التطبيق. السجلّ سجلّ الوزارة
 * (الميناء، الرقم الوظيفي، الوردية، عدد الرحلات التي عدّها)، و`user_id`
 * حسابه إن كان يعمل من التطبيق أو من بوابته على /admin.
 */
class StatisticsOfficer extends BaseModel
{
    use HasFactory;

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * الرقم الوظيفي التالي لحساب لا رقم له — السجلات المبذورة تحمل SO-01…
     */
    public static function numberFor(User $user): string
    {
        return 'SO-'.str_pad((string) $user->id, 2, '0', STR_PAD_LEFT);
    }
}
