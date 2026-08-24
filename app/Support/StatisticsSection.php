<?php

namespace App\Support;

/**
 * سجل لوحات "قسم الإحصاء" — المرجع الذي تبنى منه بوابة الإحصاء الموحدة.
 *
 * القسم يمتد على البوابتين: لوحات تنفيذية تحت /gov وسجلات تشغيلية تحت /admin.
 * لذلك تُخزَّن أسماء المسارات لا مساراتها، فيبقى الرابط صحيحًا لو نُقلت صفحة
 * بين البوابتين لاحقًا.
 */
class StatisticsSection
{
    /**
     * @return array<int, array{title: string, icon: string, tone: string, items: array<int, array{route: string, label: string, icon: string, desc: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'title' => 'اللوحات التنفيذية والمؤشرات',
                'icon' => 'crown',
                'tone' => 'sky',
                'items' => [
                    ['route' => 'stats.executive-briefing', 'label' => 'موجز الإدارة العليا', 'icon' => 'crown', 'desc' => 'موجز سريع للإنتاج وتوزيع الأسطول والاتجاه الشهري'],
                    ['route' => 'stats.national-indicators', 'label' => 'المؤشرات الوطنية', 'icon' => 'bar-chart', 'desc' => '15 مؤشرًا معتمدًا موزّعًا على المناطق'],
                    ['route' => 'stats.performance-compare', 'label' => 'مقارنة الأداء', 'icon' => 'gauge', 'desc' => 'مقارنة الإنتاج والامتثال بين الموانئ والمحافظات'],
                ],
            ],
            [
                'title' => 'الإحصاء الميداني والتتبع',
                'icon' => 'clipboard',
                'tone' => 'amber',
                'items' => [
                    ['route' => 'stats.field-statistics', 'label' => 'الإحصاء الميداني', 'icon' => 'clipboard', 'desc' => 'سجلات المصيد ومسار الاعتماد الكامل'],
                    ['route' => 'stats.approved-catch', 'label' => 'المصيد المعتمد', 'icon' => 'badge-check', 'desc' => 'السجلات المعتمدة مع التصدير'],
                    ['route' => 'stats.catch-trace', 'label' => 'تتبع المصيد', 'icon' => 'link', 'desc' => 'خط زمني للتتبع من البحر إلى السوق'],
                    ['route' => 'stats.statistics-officers', 'label' => 'موظفو الإحصاء', 'icon' => 'user-cog', 'desc' => 'إدارة موظفي الإحصاء وأدائهم'],
                    ['route' => 'discrepancy-review', 'label' => 'مراجعة الفروقات', 'icon' => 'scale', 'desc' => 'الفروقات بين إدخال الكابتن والوزن الفعلي'],
                ],
            ],
            [
                'title' => 'التحليلات الذكية والتقارير',
                'icon' => 'bot',
                'tone' => 'violet',
                'items' => [
                    ['route' => 'stats.analytics', 'label' => 'التحليلات والمؤشرات', 'icon' => 'line-chart', 'desc' => 'مقارنة المناطق والأنواع والموانئ جنبًا إلى جنب'],
                    ['route' => 'stats.ai-assistant', 'label' => 'حوات AI', 'icon' => 'bot', 'desc' => 'مساعد تحليلي يجيب من بيانات النظام'],
                    ['route' => 'stats.reports', 'label' => 'التقارير', 'icon' => 'file-text', 'desc' => '16 نوع تقرير مع تصدير CSV وطباعة'],
                    ['route' => 'stats.monthly-reports', 'label' => 'تقارير الإنتاج', 'icon' => 'file-chart', 'desc' => 'تقرير يومي وشهري وسنوي مع الطباعة'],
                    ['route' => 'stats.annual-bulletin', 'label' => 'النشرة السنوية', 'icon' => 'book-open', 'desc' => '16 صفحة A4 قابلة للطباعة'],
                ],
            ],
            [
                'title' => 'الأسواق وسلسلة الإمداد',
                'icon' => 'store',
                'tone' => 'emerald',
                'items' => [
                    ['route' => 'stats.markets', 'label' => 'الأسواق والمزادات', 'icon' => 'store', 'desc' => 'الأسعار والمبيعات والمزادات'],
                    ['route' => 'stats.supply-chain', 'label' => 'سلسلة الإمداد', 'icon' => 'truck', 'desc' => 'مخطط المراحل من المصيد إلى التسويق'],
                    ['route' => 'stats.food-security', 'label' => 'الأمن الغذائي', 'icon' => 'utensils', 'desc' => 'نسب الاكتفاء الذاتي وموازنة الواردات'],
                ],
            ],
            [
                'title' => 'الموارد والخريطة البحرية',
                'icon' => 'map',
                'tone' => 'cyan',
                'items' => [
                    ['route' => 'gov.sea-map', 'label' => 'الخريطة البحرية', 'icon' => 'map', 'desc' => 'خريطة تفاعلية للموانئ ومواقع الصيد'],
                    ['route' => 'gov.production', 'label' => 'الإنتاج السمكي', 'icon' => 'fish', 'desc' => 'تحليل الإنتاج حسب المنطقة والنوع والشهر'],
                    ['route' => 'species', 'label' => 'الأنواع السمكية', 'icon' => 'fish', 'desc' => 'حالة المخزون والأنواع المستهدفة'],
                    ['route' => 'gov.sustainability', 'label' => 'الاستدامة والمخزون', 'icon' => 'leaf', 'desc' => 'مؤشرات الاستدامة و CPUE والضغط'],
                ],
            ],
            [
                'title' => 'الامتثال والإنذارات',
                'icon' => 'shield-alert',
                'tone' => 'rose',
                'items' => [
                    ['route' => 'gov.compliance', 'label' => 'الرقابة والامتثال', 'icon' => 'shield-alert', 'desc' => 'المخالفات والإجراءات ومؤشرات الالتزام'],
                    ['route' => 'gov.alerts', 'label' => 'مركز الإنذارات', 'icon' => 'bell-ring', 'desc' => 'تنبيهات المصيد والرخص والمواسم'],
                ],
            ],
        ];
    }

    /**
     * تصفية السجل بنص البحث — يطابق اسم اللوحة ووصفها وعنوان مجموعتها.
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
