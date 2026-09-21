<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * الرحلة تحمل دورتين: `status` هي الدورة التشغيلية/الإحصائية بمفردات
 * الوزارة (تقرؤها صفحات الإحصاء كما هي)، و`sale_status` الدورة التجارية
 * للمالك — تبدأ حين يكتمل العد وتنتهي حين ينفد مصيد الرحلة بيعًا أو إرسالًا.
 */
class Trip extends BaseModel
{
    use HasFactory;

    public const SCHEDULED = 'مجدولة';

    public const AT_SEA = 'في البحر';

    public const RETURNED = 'عادت للميناء';

    public const AWAITING_COUNT = 'بانتظار الإحصاء';

    public const COUNTING = 'تحت الإحصاء';

    public const AWAITING_APPROVAL = 'بانتظار الاعتماد';

    public const APPROVED = 'معتمدة';

    public const CANCELLED = 'ملغاة';

    public const STATUSES = [
        self::SCHEDULED, self::AT_SEA, self::RETURNED, self::AWAITING_COUNT,
        self::COUNTING, self::AWAITING_APPROVAL, self::APPROVED, self::CANCELLED,
    ];

    /** الرحلة لم يُعدّ مصيدها بعد — لا يمكن بيعها. */
    public const SALE_NOT_STARTED = 'لم يبدأ';

    /** اكتمل العد وبقي من مصيدها ما يُباع. */
    public const SALE_OPEN = 'قيد البيع';

    /** نفد مصيد الرحلة بيعًا أو إرسالًا للدلال. */
    public const SALE_DONE = 'مباعة';

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
        'started_at' => 'datetime',
        'catch_submitted_at' => 'datetime',
        'received_at' => 'datetime',
        'counted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function departurePort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'departure_port_id');
    }

    public function returnPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'return_port_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counter_id');
    }

    public function tripType(): BelongsTo
    {
        return $this->belongsTo(TripType::class);
    }

    public function catchRecords(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeForCaptain(Builder $query, User $captain): Builder
    {
        return $query->where('captain_id', $captain->id);
    }

    /**
     * شاشة الكابتن "الرحلات التي بانتظارك": مسندة إليه ولم تنطلق.
     */
    public function scopeAwaitingCaptain(Builder $query): Builder
    {
        return $query->where('status', self::SCHEDULED);
    }

    /**
     * شاشة الكابتن "الرحلات النشطة": في البحر حتى يرسل مخرجاتها.
     */
    public function scopeActiveForCaptain(Builder $query): Builder
    {
        return $query->where('status', self::AT_SEA);
    }

    /**
     * الرحلات التي يتابعها المالك في شاشة "الرحلات النشطة": من الانطلاق حتى
     * ما بعد العد ما دام مصيدها لم يُبع.
     */
    public function scopeActiveForOwner(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('status', [self::AT_SEA, self::RETURNED, self::AWAITING_COUNT, self::COUNTING])
            ->orWhere('sale_status', self::SALE_OPEN));
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }

    public function isCounted(): bool
    {
        return in_array($this->status, [self::AWAITING_APPROVAL, self::APPROVED], true);
    }

    /**
     * ما يستطيعه الكابتن على الرحلة الآن — تقرؤه الشاشات وواجهة التطبيق.
     */
    public function canStart(): bool
    {
        return $this->status === self::SCHEDULED;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::SCHEDULED, self::AT_SEA], true);
    }

    public function canSubmitCatch(): bool
    {
        return $this->status === self::AT_SEA;
    }

    public function canSell(): bool
    {
        return $this->sale_status === self::SALE_OPEN;
    }

    /**
     * شريط التقدّم في تطبيق المالك: أربع خطوات فوق حالات الوزارة.
     * 0 = لم تبدأ، 1 = بدأت، 2 = بانتظار العدّاد، 3 = جارية العد، 4 = اكتمل العد.
     */
    public function getProgressStepAttribute(): int
    {
        return match ($this->status) {
            self::AT_SEA => 1,
            self::RETURNED, self::AWAITING_COUNT => 2,
            self::COUNTING => 3,
            self::AWAITING_APPROVAL, self::APPROVED => 4,
            default => 0,
        };
    }

    /**
     * تسمية الحالة كما يراها المالك والكابتن في التطبيق.
     */
    public function getAppStatusAttribute(): string
    {
        return match ($this->status) {
            self::SCHEDULED => 'بانتظار الانطلاق',
            self::AT_SEA => 'بدأت الرحلة',
            self::RETURNED, self::AWAITING_COUNT => 'بانتظار العدّاد',
            self::COUNTING => 'جارية العد',
            self::AWAITING_APPROVAL, self::APPROVED => match ($this->sale_status) {
                self::SALE_OPEN => 'اكتمل العد وجاهزة للبيع',
                self::SALE_DONE => 'مكتملة',
                // رحلة عُدّت قبل أن يكون لها مالك في النظام — لا مخزون يُباع منها.
                default => 'اكتمل العد',
            },
            self::CANCELLED => 'ملغاة',
            default => $this->status,
        };
    }

    /**
     * الرقم التالي بصيغة TR-YYYY-NNNN — تتبع تسلسل السنة الحالية.
     */
    public static function nextNumber(): string
    {
        $prefix = 'TR-'.now()->year.'-';
        $last = static::where('trip_number', 'like', $prefix.'%')->orderByDesc('trip_number')->value('trip_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
