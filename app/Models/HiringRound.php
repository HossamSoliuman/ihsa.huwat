<?php

namespace App\Models;

use Database\Factories\HiringRoundFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * جولة توظيف عدّادين تفتحها شركة التشغيل في أحد موانئها: عدد مقاعد ومدة.
 *
 * الحالة المخزّنة ما تقرّره الشركة (مسودة / مفتوحة / مغلقة)؛ أمّا قبولها
 * للطلبات فمحسوب عند القراءة: مفتوحة، واليوم داخل مدتها، وبقي فيها مقعد
 * (المقاعد ناقص المعتمدين) — فلا تحتاج مهمة مجدولة تغلقها.
 */
class HiringRound extends BaseModel
{
    /** @use HasFactory<HiringRoundFactory> */
    use HasFactory;

    public const DRAFT = 'draft';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public const STATUS_LABELS = [
        self::DRAFT => 'مسودة',
        self::OPEN => 'مفتوحة',
        self::CLOSED => 'مغلقة',
    ];

    protected $casts = [
        'opens_at' => 'date',
        'closes_at' => 'date',
        'seats' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(OperatingCompany::class, 'operating_company_id');
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CounterApplication::class);
    }

    /**
     * الجولات التي تقبل الطلبات اليوم: مفتوحة، داخل مدتها، لشركة نشطة، وبقي
     * فيها مقعد.
     */
    public function scopeAccepting(Builder $query): Builder
    {
        return $query->where('status', self::OPEN)
            ->whereDate('opens_at', '<=', today())
            ->whereDate('closes_at', '>=', today())
            ->whereHas('company', fn ($q) => $q->active())
            ->whereRaw('seats > (SELECT COUNT(*) FROM counter_applications WHERE counter_applications.hiring_round_id = hiring_rounds.id AND counter_applications.status = ?)', [CounterApplication::APPROVED]);
    }

    public function approvedCount(): int
    {
        return $this->approved_count ?? $this->applications()->where('status', CounterApplication::APPROVED)->count();
    }

    public function seatsLeft(): int
    {
        return max(0, $this->seats - $this->approvedCount());
    }

    public function isAccepting(): bool
    {
        return $this->status === self::OPEN
            && ! $this->opens_at->isFuture()
            && ! $this->closes_at->endOfDay()->isPast()
            && $this->seatsLeft() > 0;
    }

    /**
     * الحالة كما تُعرض: المفتوحة التي لم يبدأ موعدها أو انتهى أو امتلأت
     * تظهر بما هي عليه فعلًا.
     */
    public function getStateLabelAttribute(): string
    {
        if ($this->status !== self::OPEN) {
            return self::STATUS_LABELS[$this->status] ?? $this->status;
        }

        return match (true) {
            $this->seatsLeft() === 0 => 'اكتملت المقاعد',
            $this->opens_at->isFuture() => 'تبدأ '.$this->opens_at->format('Y-m-d'),
            $this->closes_at->endOfDay()->isPast() => 'انتهت مدتها',
            default => 'تستقبل الطلبات',
        };
    }

    public function getStateToneAttribute(): string
    {
        return match (true) {
            $this->status === self::DRAFT => 'badge-info',
            $this->status === self::CLOSED => 'badge-danger',
            $this->isAccepting() => 'badge-ok',
            default => 'badge-warn',
        };
    }
}
