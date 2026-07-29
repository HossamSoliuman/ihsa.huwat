<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarborLicense extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['port_id', 'license_number', 'license_type', 'license_holder_name', 'boat_number', 'issue_date', 'expiry_date', 'license_status', 'attachment_path'];

    protected $attributes = ['license_type' => 'seasonal', 'license_status' => 'valid'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
