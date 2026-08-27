<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * يحدّد البوابة النشطة داخل لوحة الوزارة وقائمتها الجانبية.
 *
 * اللوحة مقسومة إلى خمس بوابات تتشارك نفس التخطيط: لوحة الحكومة تحت /gov،
 * وقسم الإحصاء تحت /stats، وقسم الإدارة الفرعية تحت /subadmin، وقسم الخدمات
 * والتراخيص تحت /services، والمنصة التشغيلية تحت /admin. التمييز من اسم المسار
 * لا من المسار نفسه: مسارات البوابات الأربع الأولى تحمل بادئاتها، ومسارات
 * المنصة التشغيلية بلا بادئة.
 *
 * بوابة المعلومات (مركز الإدارة) خارج هذا التقسيم — لها تخطيطها وقائمتها.
 */
class Nav
{
    public const GOV = 'gov';

    public const STATS = 'stats';

    public const SUBADMIN = 'subadmin';

    public const SERVICES = 'services';

    public const OPS = 'ops';

    /**
     * مفتاح كل بوابة ومصدر قائمتها. المنصة التشغيلية آخرها لأنها الافتراضية:
     * أسماء مساراتها بلا بادئة، فتلتقط كل ما لم يطابق بادئة قبلها.
     */
    private const SECTIONS = [
        self::GOV => 'hawat.nav_gov',
        self::STATS => 'hawat.nav_stats',
        self::SUBADMIN => 'hawat.nav_subadmin',
        self::SERVICES => 'hawat.nav_services',
        self::OPS => 'hawat.nav',
    ];

    /**
     * مفاتيح البوابات بترتيب عرضها.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::SECTIONS);
    }

    /**
     * مفتاح البوابة النشطة في الطلب الحالي.
     */
    public static function portalKey(): string
    {
        $route = Route::currentRouteName() ?? '';

        foreach ([self::GOV, self::STATS, self::SUBADMIN, self::SERVICES] as $key) {
            if (str_starts_with($route, $key.'.')) {
                return $key;
            }
        }

        return self::OPS;
    }

    /**
     * بيانات البوابة النشطة (الاسم، الأيقونة، مسار الرئيسية).
     *
     * @return array{label: string, icon: string, home: string}
     */
    public static function portal(?string $key = null): array
    {
        return config('hawat.portals.'.($key ?? self::portalKey()));
    }

    /**
     * البوابات الأخرى غير النشطة — روابط التنقّل في أسفل القائمة الجانبية.
     *
     * @return array<int, array{label: string, icon: string, home: string}>
     */
    public static function otherPortals(): array
    {
        $active = self::portalKey();

        return array_map(
            fn (string $key) => self::portal($key),
            array_values(array_filter(self::keys(), fn (string $key) => $key !== $active)),
        );
    }

    /**
     * وضع العرض: تُطوى القائمة الجانبية والشريط العلوي ويُكبَّر القياس.
     *
     * لوحة الحكومة تُفتح عليه افتراضًا لأنها تُعرض على شاشة قاعة، وبقية البوابات
     * لا تدخله إلا بطلبه في ?screen=1. و?screen=0 يعيد التخطيط الكامل في أي منها.
     */
    public static function screenMode(): bool
    {
        return request()->boolean('screen', self::portalKey() === self::GOV);
    }

    /**
     * أقسام القائمة الجانبية للبوابة النشطة.
     */
    public static function sections(?string $key = null): array
    {
        return config(self::SECTIONS[$key ?? self::portalKey()]);
    }

    /**
     * عنوان الصفحة المقابل لاسم مسار، بالبحث في قوائم البوابات كلها.
     */
    public static function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::keys() as $key) {
            foreach (self::sections($key) as $section) {
                foreach ($section['items'] as $item) {
                    if ($item['route'] === $routeName) {
                        return $item['label'];
                    }
                }
            }
        }

        return null;
    }
}
