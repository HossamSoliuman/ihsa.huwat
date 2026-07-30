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
        foreach ([
            [
                'role' => 'super_admin',
                'username' => 'admin',
                'full_name' => 'IHSA System Administrator',
                'email' => 'admin@ihsa.huwat.sa',
            ],
            [
                'role' => 'government_admin',
                'username' => 'government_admin',
                'full_name' => 'Government Portal Administrator',
                'email' => 'government@ihsa.huwat.sa',
            ],
        ] as $account) {
            $role = Role::query()->where('code', $account['role'])->firstOrFail();

            User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'role_id' => $role->id,
                    'full_name' => $account['full_name'],
                    'email' => $account['email'],
                    'password_hash' => Hash::make('password'),
                    'region_id' => null,
                    'governorate_id' => null,
                    'port_id' => null,
                    'is_active' => true,
                ],
            );
        }
    }
}
