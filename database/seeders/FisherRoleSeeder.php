<?php

namespace Database\Seeders;

use App\Models\FisherRole;
use Illuminate\Database\Seeder;

class FisherRoleSeeder extends Seeder
{
    public const ROWS = [
        ['قبطان', 'Captain'],
        ['بحّار', 'Sailor'],
        ['ميكانيكي', 'Mechanic'],
        ['طبّاخ', 'Cook'],
        ['مساعد', 'Helper'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            FisherRole::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
