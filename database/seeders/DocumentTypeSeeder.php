<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * أنواع وثائق الأسطول — للقارب (الاستمارة، الرخصة، التأمين، شهادة السلامة)
 * ولفرد الطاقم (الإقامة، الجواز، رخصة البحار، التأمين الطبي).
 */
class DocumentTypeSeeder extends Seeder
{
    public const ROWS = [
        ['استمارة القارب', 'Boat registration'],
        ['رخصة صيد', 'Fishing license'],
        ['تأمين القارب', 'Boat insurance'],
        ['شهادة سلامة', 'Safety certificate'],
        ['إقامة', 'Residence permit (Iqama)'],
        ['جواز سفر', 'Passport'],
        ['رخصة بحار', 'Seaman license'],
        ['تأمين طبي', 'Medical insurance'],
        ['أخرى', 'Other'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn]) {
            DocumentType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'display_order' => $order]);
        }
    }
}
