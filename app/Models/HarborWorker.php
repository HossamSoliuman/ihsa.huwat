<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarborWorker extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['port_id', 'employee_name', 'identity_number', 'nationality', 'worker_type', 'mobile_number', 'employment_status', 'start_date', 'end_date'];

    protected $hidden = ['identity_number'];

    protected $attributes = ['nationality' => 'saudi', 'employment_status' => 'active'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
