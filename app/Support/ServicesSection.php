<?php

namespace App\Support;

/**
 * سجل لوحات "قسم الخدمات والتراخيص" — المرجع الذي تبنى منه بوابة القسم.
 *
 * القسم هو الوجه الخدمي للقطاع: ما يصل من الصيادين من طلبات، ومن يعالجه
 * ويعتمده، والرخص التي تنتهي إليها الطلبات، والرقابة على من يخالف شروطها،
 * ثم الدعم الفني لمستخدمي المنصة أنفسهم.
 *
 * "رخص المواسم" و"الرقابة والامتثال" انتقلتا إلى هنا من المنصة التشغيلية
 * ولوحة الحكومة: الرخصة والمخالفة طرفا الدورة نفسها، وفصلهما عن الطلب الذي
 * أنشأها كان يقطع المسار في منتصفه.
 */
class ServicesSection
{
    /**
     * @return array<int, array{title: string, icon: string, tone: string, items: array<int, array{route: string, label: string, icon: string, desc: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'title' => 'استقبال الطلبات ومعالجتها',
                'icon' => 'inbox',
                'tone' => 'sky',
                'items' => [
                    ['route' => 'services.fisher-services', 'label' => 'خدمات الصيادين', 'icon' => 'headset', 'desc' => 'طلبات التجديد والإصدار والاستبدال ونقل الميناء من التقديم إلى الاعتماد'],
                    ['route' => 'services.my-workspace', 'label' => 'مساحتي', 'icon' => 'user', 'desc' => 'ما ينتظر الموظف وحده — إجراءات فورية ومهام وتنبيهات وتقرير أداء'],
                ],
            ],
            [
                'title' => 'الموظفون والصلاحيات',
                'icon' => 'user-cog',
                'tone' => 'amber',
                'items' => [
                    ['route' => 'services.staff-dashboard', 'label' => 'لوحة الموظف', 'icon' => 'shield-check', 'desc' => 'قوائم الاعتماد والمعالجة لكل موظف داخل نطاقه الجغرافي'],
                    ['route' => 'services.staff-management', 'label' => 'إدارة الموظفين', 'icon' => 'user-cog', 'desc' => 'إسناد الموظفين للأقسام وتخويلهم بالخدمات وضبط صلاحياتهم'],
                ],
            ],
            [
                'title' => 'الرخص والامتثال',
                'icon' => 'ticket',
                'tone' => 'violet',
                'items' => [
                    ['route' => 'services.season-licenses', 'label' => 'رخص المواسم', 'icon' => 'ticket', 'desc' => 'إصدار رخص المواسم وربطها بالقوارب والتحقق من صلاحيتها'],
                    ['route' => 'services.compliance', 'label' => 'الرقابة والامتثال', 'icon' => 'shield-alert', 'desc' => 'المخالفات والغرامات والإجراءات المتخذة على القوارب'],
                ],
            ],
            [
                'title' => 'الدعم الفني',
                'icon' => 'life-buoy',
                'tone' => 'emerald',
                'items' => [
                    ['route' => 'services.support', 'label' => 'الدعم الفني', 'icon' => 'life-buoy', 'desc' => 'تذاكر المستخدمين الداخليين وإسنادها لفريق الدعم حتى إغلاقها'],
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
