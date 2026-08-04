<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationSubmissionEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['submission_id', 'event_type', 'from_status', 'to_status', 'note', 'actor_user_id'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(InformationSubmission::class, 'submission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
