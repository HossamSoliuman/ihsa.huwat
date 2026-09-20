<?php

namespace Database\Seeders;

use App\Models\IdType;
use Illuminate\Database\Seeder;

class IdTypeSeeder extends Seeder
{
    public const ROWS = [
        ['هوية وطنية', 'National ID'],
        ['إقامة', 'Residence permit'],
        ['جواز سفر', 'Passport'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            IdType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
