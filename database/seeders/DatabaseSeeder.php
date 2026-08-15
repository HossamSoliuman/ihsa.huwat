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
            SeasonsAndMarketsSeeder::class,
            GovernanceSeeder::class,
            SystemSeeder::class,
        ]);
    }
}