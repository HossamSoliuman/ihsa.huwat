<?php

namespace Database\Seeders;

use App\Models\BoatType;
use Illuminate\Database\Seeder;

class BoatTypeSeeder extends Seeder
{
    public const ROWS = [
        ['طراد', 'Cruiser'],
        ['لنش', 'Launch'],
        ['قارب تقليدي', 'Traditional boat'],
        ['قارب فايبر', 'Fiberglass boat'],
        ['قارب خشبي', 'Wooden boat'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            BoatType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
