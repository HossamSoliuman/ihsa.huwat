<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public const ROWS = [
        ['نقدًا', 'Cash'],
        ['بطاقة', 'Card'],
        ['تحويل بنكي', 'Bank transfer'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            PaymentMethod::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
