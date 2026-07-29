<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeAction
{
    public function execute(array $attributes): Employee
    {
        return DB::transaction(function () use ($attributes): Employee {
            $user = User::query()->create([
                'role_id' => Role::query()->where('code', 'stat_employee')->valueOrFail('id'),
                'full_name' => $attributes['full_name'], 'username' => $attributes['username'],
                'password_hash' => Hash::make($attributes['password']), 'is_active' => true,
            ]);

            return $user->employee()->create([
                'hire_date' => $attributes['hire_date'], 'contract_type' => $attributes['contract_type'],
                'contract_end_date' => $attributes['contract_end_date'] ?? null, 'status' => 'active',
            ]);
        });
    }
}
