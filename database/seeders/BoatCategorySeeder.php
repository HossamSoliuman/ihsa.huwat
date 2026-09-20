<?php

namespace Database\Seeders;

use App\Models\BoatCategory;
use Illuminate\Database\Seeder;

class BoatCategorySeeder extends Seeder
{
    public const ROWS = [
        ['قارب صيد صغير', 'Small fishing boat'],
        ['قارب صيد متوسط', 'Medium fishing boat'],
        ['لنش صيد', 'Fishing launch'],
        ['سفينة صيد', 'Fishing vessel'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            BoatCategory::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
