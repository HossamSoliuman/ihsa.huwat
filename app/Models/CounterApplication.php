<?php

namespace App\Models;

use Database\Factories\CounterApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب توظيف عدّاد في جولة توظيف. يُقدَّم من الويب أو التطبيق بلا حساب،
 * ويُتابَع بمفتاحه (token)، ولا يصل الشركة إلا بعد توثيق الجوال.
 * الاعتماد والرفض في App\Services\Counters\CounterApplicationReview.
 */
class CounterApplication extends Model
{
    /** @use HasFactory<CounterApplicationFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const WITHDRAWN = 'withdrawn';

    public const STATUS_LABELS = [
        self::PENDING => 'بانتظار المراجعة',
        self::APPROVED => 'مقبول',
        self::REJECTED => 'مرفوض',
        self::WITHDRAWN => 'مسحوب',
    ];

    protected $fillable = [
        'hiring_round_id',
        'operating_company_id',
        'port_id',
        'token',
        'name',
        'phone',
        'national_id',
        'email',
        'birth_date',
        'qualification',
        'experience_years',
        'notes',
        'password',
        'phone_verified_at',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'user_id',
        'ip',
    ];

    protected $hidden = ['password', 'token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'birth_date' => 'date',
            'experience_years' => 'integer',
            'phone_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(HiringRound::class, 'hiring_round_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(OperatingCompany::class, 'operating_company_id');
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ما تراه الشركة: الطلبات التي وُثّق جوالها.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('phone_verified_at');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isPending() && ! $this->isVerified()) {
            return 'بانتظار توثيق الجوال';
        }

        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::APPROVED => 'badge-ok',
            self::REJECTED => 'badge-danger',
            self::WITHDRAWN => 'badge-info',
            default => 'badge-warn',
        };
    }
}
