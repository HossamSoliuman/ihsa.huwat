<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['username', 'ip_address', 'success'];

    protected function casts(): array
    {
        return ['success' => 'boolean'];
    }
}
