<?php

namespace App\Models;

use Database\Factories\RegistrationRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب تسجيل مالك أو دلال من صفحة الهبوط، ينتظر مراجعة المدير العام.
 * الاعتماد والرفض في App\Services\Registration\RegistrationReview.
 */
class RegistrationRequest extends Model
{
    /** @use HasFactory<RegistrationRequestFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::PENDING => 'بانتظار المراجعة',
        self::APPROVED => 'معتمد',
        self::REJECTED => 'مرفوض',
    ];

    /** الدوران اللذان يُسجَّل لهما من الصفحة العامة؛ بقية الأدوار ينشئها المدير أو المالك. */
    public const ROLES = [Role::OWNER, Role::DALAL];

    protected $fillable = [
        'role_id',
        'name',
        'phone',
        'email',
        'business_name',
        'city',
        'boats_count',
        'notes',
        'password',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'user_id',
        'ip',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'boats_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
