<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'updated',
            'model_type' => Employee::class,
            'model_id' => Employee::factory(),
            'old_values' => null,
            'new_values' => ['status' => 'active'],
            'reason' => null,
            'ip_address' => '127.0.0.1',
        ];
    }
}
