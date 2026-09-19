<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GeographicSeeder::class,
            FleetSeeder::class,
            SeasonsSeeder::class,
            OperationsSeeder::class,
            MarketsSeeder::class,
            GovernanceSeeder::class,
            SystemSeeder::class,
            RoleSeeder::class,
            // بعد SystemSeeder وRoleSeeder: حسابات الدخول تُنسب إلى الصلاحيات
            // التي يبذرها الأول وإلى أدوار التطبيق التي يبذرها الثاني.
            UserSeeder::class,
            SubAdministrationSeeder::class,
            ServicesLicensingSeeder::class,
        ]);
    }
}
