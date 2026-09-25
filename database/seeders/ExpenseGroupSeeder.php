<?php

namespace Database\Seeders;

use App\Models\ExpenseGroup;
use Illuminate\Database\Seeder;

class ExpenseGroupSeeder extends Seeder
{
    public const ROWS = [
        ['مصروفات تشغيلية', 'Operating expenses'],
        [ExpenseGroup::MAINTENANCE, 'Maintenance expenses'],
        ['مصروفات حكومية', 'Government expenses'],
        ['مصروفات عامة', 'General expenses'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            ExpenseGroup::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
