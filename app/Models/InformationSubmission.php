<?php

namespace App\Models;

use App\Actions\Information\Dashboard\Support\DashboardCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformationSubmission extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUSES = ['submitted', 'under_review', 'needs_edit', 'approved', 'rejected'];

    /**
     * Statuses a reviewer may move a submission to, keyed by its current status.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'submitted' => ['under_review', 'needs_edit', 'approved', 'rejected'],
        'under_review' => ['needs_edit', 'approved', 'rejected'],
        'needs_edit' => ['under_review', 'approved', 'rejected'],
        'approved' => ['under_review'],
        'rejected' => ['under_review'],
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'submitted' => 'جديد',
        'under_review' => 'تحت المراجعة',
        'needs_edit' => 'بانتظار التعديل',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
    ];

    protected $attributes = ['status' => self::STATUS_SUBMITTED];

    protected $fillable = [
        'reference_no',
        'status',
        'submitted_by',
        'port_id',
        'boat_id',
        'captain_id',
        'owner_full_name',
        'owner_national_id',
        'owner_nationality',
        'owner_birth_date',
        'owner_email',
        'owner_phone',
        'owner_region',
        'owner_governorate',
        'owner_address',
        'crew_count',
        'fishing_method',
        'license_number',
        'license_issue_date',
        'license_expiry_date',
        'boat_data',
        'captain_data',
        'crew_members',
        'fishing_tools',
        'document_path',
        'document_paths',
        'captain_photo_path',
        'consented_at',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $hidden = [
        'owner_national_id',
        'captain_data',
        'crew_members',
        'document_path',
        'document_paths',
        'captain_photo_path',
    ];

    protected static function booted(): void
    {
        $forgetDashboard = static function (): void {
            DashboardCache::forget([
                DashboardCache::REGISTRY,
                DashboardCache::REVIEW,
                DashboardCache::GEOGRAPHY,
                DashboardCache::FLEET,
                DashboardCache::LICENCE,
                DashboardCache::COMPLETENESS,
                DashboardCache::ATTENTION,
            ]);
        };

        static::saved($forgetDashboard);
        static::deleted($forgetDashboard);
    }

    protected function casts(): array
    {
        return [
            'owner_birth_date' => 'date',
            'license_issue_date' => 'date',
            'license_expiry_date' => 'date',
            'boat_data' => 'array',
            'captain_data' => 'array',
            'crew_members' => 'array',
            'fishing_tools' => 'array',
            'document_paths' => 'array',
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Restrict the query to the submissions filed by one applicant. Submissions are
     * keyed by the owner's national id and phone, the pair the portal verifies.
     *
     * @param  Builder<self>  $query
     * @param  array{national_id: string, phone: string}  $identity
     * @return Builder<self>
     */
    public function scopeForIdentity(Builder $query, array $identity): Builder
    {
        return $query
            ->where('owner_national_id', $identity['national_id'])
            ->where('owner_phone', $identity['phone']);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isDecided(): bool
    {
        return in_array($this->status, ['approved', 'rejected'], true);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Captain::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InformationSubmissionEvent::class, 'submission_id');
    }
}
