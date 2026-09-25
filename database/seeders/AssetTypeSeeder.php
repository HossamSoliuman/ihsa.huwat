<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    public const ROWS = [
        ['قارب', 'Boat'],
        ['محرك', 'Engine'],
        ['معدات صيد', 'Fishing equipment'],
        ['أجهزة ملاحة واتصال', 'Navigation & radio'],
        ['مركبة', 'Vehicle'],
        ['مستودع وتبريد', 'Storage & cooling'],
        ['أخرى', 'Other'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            AssetType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
