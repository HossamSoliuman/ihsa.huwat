<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
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
        'owner_city',
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
    ];

    protected $hidden = [
        'owner_national_id',
        'captain_data',
        'crew_members',
        'document_path',
        'document_paths',
        'captain_photo_path',
    ];

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
        ];
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
}
