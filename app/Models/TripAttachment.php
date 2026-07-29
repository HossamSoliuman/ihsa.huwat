<?php

namespace App\Models;

use Database\Factories\TripAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAttachment extends Model
{
    /** @use HasFactory<TripAttachmentFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['trip_id', 'type', 'file_path', 'uploaded_at'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
