<?php

namespace App\Support;

/**
 * سجل لوحات "قسم الإدارة الفرعية" — المرجع الذي تبنى منه بوابة القسم.
 *
 * القسم يجمع ما كان مبعثرًا في قائمتي لوحة الحكومة والمنصة التشغيلية تحت مظلة
 * واحدة: مركز الإدارة والصلاحيات والهيكل التنظيمي، ثم متابعة المهام والتنبيهات،
 * ثم التدقيق والإنذارات والإعدادات.
 *
 * تُخزَّن أسماء المسارات لا مساراتها، فيبقى الرابط صحيحًا لو نُقلت صفحة لاحقًا —
 * و"مركز الإدارة" منها، فهو يشير إلى بوابة المعلومات على مضيفها المستقل.
 */
class AdminSection
{
    /**
     * @return array<int, array{title: string, icon: string, tone: string, items: array<int, array{route: string, label: string, icon: string, desc: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'title' => 'مركز الإدارة والصلاحيات',
                'icon' => 'shield-check',
                'tone' => 'sky',
                'items' => [
                    ['route' => 'admin.index', 'label' => 'مركز الإدارة', 'icon' => 'shield-check', 'desc' => 'إدارة البيانات الأساسية والتكاملات في بوابة المعلومات'],
                    ['route' => 'subadmin.users', 'label' => 'المستخدمون والصلاحيات', 'icon' => 'user-cog', 'desc' => 'الأدوار والنطاق الجغرافي لكل مستخدم (RBAC)'],
                    ['route' => 'subadmin.org-structure', 'label' => 'الهيكل التنظيمي', 'icon' => 'network', 'desc' => 'المناصب وشاغلوها وربطها بأدوار النظام'],
                ],
            ],
            [
                'title' => 'المهام والتنبيهات الإدارية',
                'icon' => 'calendar-days',
                'tone' => 'amber',
                'items' => [
                    ['route' => 'subadmin.admin-tasks', 'label' => 'تقويم المهام الإدارية', 'icon' => 'calendar-days', 'desc' => 'مهام كل قسم على تقويم شهري مع الصلاحية المطلوبة'],
                    ['route' => 'subadmin.staff-notifications', 'label' => 'التنبيهات الإدارية', 'icon' => 'bell-ring', 'desc' => 'إشعارات الطلبات وما ينتظر الاعتماد الإداري'],
                ],
            ],
            [
                'title' => 'التدقيق والإنذارات',
                'icon' => 'history',
                'tone' => 'violet',
                'items' => [
                    ['route' => 'subadmin.audit-log', 'label' => 'سجل العمليات', 'icon' => 'history', 'desc' => 'تتبّع كامل لكل إنشاء وتعديل واعتماد في النظام'],
                    ['route' => 'subadmin.alerts', 'label' => 'مركز الإنذارات', 'icon' => 'bell-ring', 'desc' => 'إنذارات الاستدامة والرخص مع إسناد المسؤول وإغلاقها'],
                ],
            ],
            [
                'title' => 'الإعدادات',
                'icon' => 'settings',
                'tone' => 'emerald',
                'items' => [
                    ['route' => 'subadmin.settings', 'label' => 'الإعدادات', 'icon' => 'settings', 'desc' => 'تفضيلات الإشعارات وإعدادات النظام والتكامل'],
                ],
            ],
        ];
    }

    /**
     * تصفية السجل بنص البحث — يطابق اسم اللوحة ووصفها.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(?string $query): array
    {
        $query = trim((string) $query);

        if ($query === '') {
            return self::groups();
        }

        $matches = fn (string $haystack) => mb_stripos($haystack, $query) !== false;

        return array_values(array_filter(array_map(function (array $group) use ($matches) {
            $group['items'] = array_values(array_filter(
                $group['items'],
                fn (array $item) => $matches($item['label']) || $matches($item['desc']),
            ));

            return $group;
        }, self::groups()), fn (array $group) => $group['items'] !== []));
    }

    /**
     * عدد اللوحات في القسم كاملًا.
     */
    public static function dashboardCount(): int
    {
        return array_sum(array_map(fn (array $group) => count($group['items']), self::groups()));
    }
}
