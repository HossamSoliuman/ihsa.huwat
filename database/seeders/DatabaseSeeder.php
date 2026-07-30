<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LegacyDataSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            ShiftSeeder::class,
            FishSpeciesSeeder::class,
        ]);
    }
}
