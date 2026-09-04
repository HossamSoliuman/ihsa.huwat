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
            // بعد SystemSeeder: حسابات الدخول تُنسب إلى الصلاحيات التي يبذرها.
            UserSeeder::class,
            SubAdministrationSeeder::class,
            ServicesLicensingSeeder::class,
        ]);
    }
}