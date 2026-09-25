<?php

namespace Database\Seeders;

use App\Models\DalalWorkerType;
use Illuminate\Database\Seeder;

class DalalWorkerTypeSeeder extends Seeder
{
    public const ROWS = [
        ['بائع', 'Seller'],
        ['حمّال', 'Porter'],
        ['محاسب', 'Accountant'],
        ['سائق', 'Driver'],
        ['عامل تنظيف وتجهيز', 'Cleaner'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            DalalWorkerType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
