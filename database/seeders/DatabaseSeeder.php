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
            DepartmentSeeder::class,
            JobTitleSeeder::class,
            BankSeeder::class,
            LeaveTypeSeeder::class,
            SalaryComponentSeeder::class,
            ShiftSeeder::class,
            /** Families first: the species seeder files each code under the family it belongs to. */
            FishFamilySeeder::class,
            FishSpeciesSeeder::class,
            NationalitySeeder::class,
            BoatTypeSeeder::class,
            BoatClassificationSeeder::class,
            HullMaterialSeeder::class,
            CrewRoleSeeder::class,
            MarketJobTitleSeeder::class,
            FishingMethodSeeder::class,
            FishingToolTypeSeeder::class,
            FishingToolMaterialSeeder::class,
            FishingToolConditionSeeder::class,
        ]);
    }
}
