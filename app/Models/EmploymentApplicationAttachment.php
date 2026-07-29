<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentApplicationAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['application_id', 'attachment_type', 'original_name', 'stored_path', 'mime_type', 'file_size'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EmploymentApplication::class, 'application_id');
    }
}
