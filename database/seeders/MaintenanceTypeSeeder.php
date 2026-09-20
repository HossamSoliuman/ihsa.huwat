<?php

namespace Database\Seeders;

use App\Models\MaintenanceType;
use Illuminate\Database\Seeder;

class MaintenanceTypeSeeder extends Seeder
{
    public const ROWS = [
        ['صيانة محرك', 'Engine maintenance'],
        ['صيانة هيكل', 'Hull maintenance'],
        ['صيانة كهرباء', 'Electrical maintenance'],
        ['فحص دوري', 'Periodic inspection'],
        ['طلاء', 'Painting'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            MaintenanceType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
