<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['governorate_id', 'name'];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
