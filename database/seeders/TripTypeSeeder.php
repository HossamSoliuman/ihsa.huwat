<?php

namespace Database\Seeders;

use App\Models\TripType;
use Illuminate\Database\Seeder;

class TripTypeSeeder extends Seeder
{
    public const ROWS = [
        ['الصيد التجاري', 'Commercial fishing'],
        ['الصيد الحرفي (صياد فردي)', 'Artisanal fishing'],
        ['قارب نزهة (خاص)', 'Private leisure boat'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            TripType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
