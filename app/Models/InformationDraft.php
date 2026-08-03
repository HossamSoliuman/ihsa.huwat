<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payload',
        'current_step',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'current_step' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
