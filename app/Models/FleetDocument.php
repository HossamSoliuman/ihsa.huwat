<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * وثيقة قارب أو فرد طاقم (استمارة، رخصة، تأمين، إقامة…) بتاريخ انتهائها —
 * منها تنبيهات الأسطول وامتثال الطاقم.
 */
class FleetDocument extends BaseModel
{
    use HasFactory;

    /** الأيام قبل الانتهاء التي تُعدّ فيها الوثيقة "تنتهي قريبًا". */
    public const EXPIRING_DAYS = 30;

    public const VALID = 'سارية';

    public const EXPIRING = 'تنتهي قريبًا';

    public const EXPIRED = 'منتهية';

    public const NO_EXPIRY = 'بلا انتهاء';

    /** نوع الحائز كما يصل من النموذج ← صنفه. */
    public const HOLDERS = ['boat' => Boat::class, 'crew' => Fisher::class];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    /**
     * المنتهية أو التي تنتهي خلال المهلة.
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(self::EXPIRING_DAYS));
    }

    public static function statusFor(?\DateTimeInterface $expiry): string
    {
        return match (true) {
            $expiry === null => self::NO_EXPIRY,
            $expiry < now()->startOfDay() => self::EXPIRED,
            $expiry <= now()->addDays(self::EXPIRING_DAYS)->endOfDay() => self::EXPIRING,
            default => self::VALID,
        };
    }

    public function getStatusAttribute(): string
    {
        return self::statusFor($this->expiry_date);
    }

    public function getHolderKindAttribute(): string
    {
        return array_search($this->documentable_type, self::HOLDERS, true) ?: 'boat';
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}
