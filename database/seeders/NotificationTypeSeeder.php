<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

/**
 * أنواع إشعارات التطبيق كما تظهر في شاشة الإشعارات: الأربعة الأولى للكابتن
 * (شاشاته في التطبيق)، ثم ما يصل المالك عمّا يفعله كابتنه والعدّاد، ثم ما
 * يصل العدّاد من رحلات عادت إلى ميناء عمله بانتظار العد.
 */
class NotificationTypeSeeder extends Seeder
{
    public const ROWS = [
        ['رحلة جديدة بانتظارك', 'New trip assigned', 'route'],
        ['الرحلة قيد التنفيذ', 'Trip in progress', 'waves'],
        ['تم إلغاء الرحلة', 'Trip cancelled', 'x-circle'],
        ['الرحلة مكتملة', 'Trip completed', 'check-check'],
        ['تم إرسال المخرجات', 'Catch submitted', 'fish'],
        ['اكتمل العد', 'Count completed', 'clipboard'],
        ['رحلة بانتظار العد', 'Trip awaiting count', 'clipboard-check'],
    ];

    public function run(): void
    {
        foreach (self::ROWS as $order => [$name, $nameEn, $icon]) {
            NotificationType::updateOrCreate(['name' => $name], ['name_en' => $nameEn, 'icon' => $icon, 'display_order' => $order]);
        }
    }
}
