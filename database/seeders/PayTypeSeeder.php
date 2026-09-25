<?php

namespace Database\Seeders;

use App\Models\PayType;
use Illuminate\Database\Seeder;

class PayTypeSeeder extends Seeder
{
    public const ROWS = [
        [PayType::SHARE, 'Profit share'],
        [PayType::FIXED, 'Fixed salary'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            PayType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
