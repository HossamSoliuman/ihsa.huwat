<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarborViolation extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['port_id', 'violation_number', 'violation_type', 'violation_description', 'violation_date', 'boat_id', 'boat_owner_name', 'fine_amount', 'violation_status', 'created_by', 'attachment_path'];

    protected $attributes = ['fine_amount' => 0, 'violation_status' => 'open'];

    protected function casts(): array
    {
        return ['violation_date' => 'datetime', 'fine_amount' => 'decimal:2'];
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
