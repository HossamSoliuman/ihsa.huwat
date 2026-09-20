<?php

namespace Database\Seeders;

use App\Models\PaymentStatus;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    public const ROWS = [
        ['غير مدفوع', 'Unpaid'],
        ['مدفوع جزئيًا', 'Partially paid'],
        ['مدفوع', 'Paid'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            PaymentStatus::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
