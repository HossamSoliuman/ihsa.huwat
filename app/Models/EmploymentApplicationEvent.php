<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['application_id', 'event_type', 'from_status', 'to_status', 'note', 'actor_user_id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EmploymentApplication::class, 'application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
