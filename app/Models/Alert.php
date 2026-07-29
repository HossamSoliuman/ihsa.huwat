<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['type', 'message', 'related_trip_id', 'related_port_id', 'related_employee_id', 'severity', 'is_resolved', 'resolved_at'];

    protected $attributes = ['severity' => 'warning', 'is_resolved' => false];

    protected function casts(): array
    {
        return ['is_resolved' => 'boolean', 'resolved_at' => 'datetime'];
    }
}
