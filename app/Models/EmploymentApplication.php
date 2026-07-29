<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentApplication extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUSES = [
        'submitted', 'under_review', 'shortlisted', 'interview', 'accepted',
        'rejected', 'account_created', 'withdrawn',
    ];

    public const TRANSITIONS = [
        'submitted' => ['under_review', 'rejected'],
        'under_review' => ['shortlisted', 'interview', 'accepted', 'rejected'],
        'shortlisted' => ['under_review', 'interview', 'accepted', 'rejected'],
        'interview' => ['under_review', 'shortlisted', 'accepted', 'rejected'],
        'accepted' => ['under_review', 'rejected'],
        'rejected' => ['under_review'],
        'account_created' => [],
        'withdrawn' => [],
    ];

    protected $attributes = ['status' => self::STATUS_SUBMITTED, 'consent' => false];

    protected $fillable = [
        'job_id', 'reference_no', 'status', 'full_name', 'nationality',
        'identity_type', 'identity_number', 'birth_date', 'gender',
        'marital_status', 'children_count', 'mobile', 'phone', 'email', 'city',
        'address', 'preferred_port_id', 'work_type', 'source', 'education_level',
        'specialization', 'institution', 'graduation_year', 'experience_years',
        'current_employer', 'current_job_title', 'professional_summary', 'skills',
        'availability_date', 'cover_letter', 'consent', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'availability_date' => 'date',
            'consent' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'experience_years' => 'decimal:1',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(EmploymentJob::class, 'job_id');
    }

    public function preferredPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'preferred_port_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmploymentApplicationAttachment::class, 'application_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmploymentApplicationEvent::class, 'application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function employeeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }
}
