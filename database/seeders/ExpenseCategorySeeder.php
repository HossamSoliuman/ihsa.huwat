<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\ExpenseGroup;
use Illuminate\Database\Seeder;

/**
 * فئات المصروفات بمجموعاتها — منقولة من شجرة الفئات في hispa.
 */
class ExpenseCategorySeeder extends Seeder
{
    public const ROWS = [
        'مصروفات تشغيلية' => [
            ['وقود', 'Fuel'],
            ['ثلج', 'Ice'],
            ['زيت', 'Oil'],
            ['إعاشة (أكل)', 'Provisions'],
            ['ماء للشرب', 'Drinking water'],
            ['غاز', 'Gas'],
            ['أدوات صيد', 'Fishing tools'],
            [ExpenseCategory::FISHING_EQUIPMENT, 'Fishing equipment'],
        ],
        ExpenseGroup::MAINTENANCE => [
            [ExpenseCategory::BOAT_MAINTENANCE, 'Boat maintenance'],
            ['قطع غيار', 'Spare parts'],
            ['أجور إصلاح', 'Repair costs'],
            ['صيانة طارئة', 'Emergency maintenance'],
        ],
        'مصروفات حكومية' => [
            ['رسوم تجديد', 'Renewal fees'],
            ['تأمين طبي', 'Medical insurance'],
            ['مكتب العمل', 'Labor office charges'],
            ['استقدام', 'Recruitment costs'],
            ['غرامات', 'Penalties & fines'],
        ],
        'مصروفات عامة' => [
            ['إيجار', 'Rent'],
            ['كهرباء', 'Electricity'],
            ['ماء', 'Water'],
            ['اتصالات', 'Telecommunications'],
            ['نقل', 'Transport'],
            ['مصروفات أخرى', 'Miscellaneous'],
        ],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::ROWS as $group => $rows) {
            $groupId = ExpenseGroup::named($group)->id;

            foreach ($rows as [$name, $nameEn]) {
                ExpenseCategory::updateOrCreate(['name' => $name], [
                    'expense_group_id' => $groupId,
                    'name_en' => $nameEn,
                    'display_order' => $order++,
                ]);
            }
        }
    }
}
