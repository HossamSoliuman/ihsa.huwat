<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdministratorRole = Role::query()
            ->where('code', 'super_admin')
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'role_id' => $superAdministratorRole->id,
                'full_name' => 'System Administrator',
                'email' => 'admin@example.com',
                'password_hash' => Hash::make('password'),
                'region_id' => null,
                'governorate_id' => null,
                'port_id' => null,
                'is_active' => true,
            ],
        );
    }
}
