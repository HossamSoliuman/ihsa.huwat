<?php

namespace Database\Seeders;

use App\Models\StockMovementType;
use Illuminate\Database\Seeder;

class StockMovementTypeSeeder extends Seeder
{
    public const ROWS = [
        ['إدخال مصيد', 'Catch intake'],
        ['بيع', 'Sale'],
        ['إرسال لدلال', 'Consignment out'],
        ['استلام من مالك', 'Consignment in'],
        ['مرتجع', 'Return'],
        ['تسوية', 'Adjustment'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            StockMovementType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
