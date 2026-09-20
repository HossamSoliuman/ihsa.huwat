<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    public const ROWS = [
        ['محاسب', 'Accountant'],
        ['مشرف', 'Supervisor'],
        ['سائق', 'Driver'],
        ['عامل', 'Worker'],
        ['إداري', 'Administrator'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            JobTitle::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
