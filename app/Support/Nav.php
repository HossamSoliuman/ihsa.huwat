<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * يحدّد البوابة النشطة داخل لوحة الوزارة وقائمتها الجانبية.
 *
 * اللوحة مقسومة إلى بوابتين تتشاركان نفس التخطيط: لوحة الحكومة تحت /gov،
 * والمنصة التشغيلية تحت /admin. التمييز من اسم المسار لا من المسار نفسه: كل
 * مسارات لوحة الحكومة تحمل البادئة `gov.`، ومسارات المنصة التشغيلية بلا بادئة.
 *
 * بوابة المعلومات (مركز الإدارة) خارج هذا التقسيم — لها تخطيطها وقائمتها.
 */
class Nav
{
    public const GOV = 'gov';

    public const OPS = 'ops';

    /**
     * مفتاح البوابة النشطة في الطلب الحالي.
     */
    public static function portalKey(): string
    {
        return str_starts_with(Route::currentRouteName() ?? '', 'gov.') ? self::GOV : self::OPS;
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
     * أقسام القائمة الجانبية للبوابة النشطة.
     */
    public static function sections(?string $key = null): array
    {
        return config(($key ?? self::portalKey()) === self::GOV ? 'hawat.nav_gov' : 'hawat.nav');
    }

    /**
     * عنوان الصفحة المقابل لاسم مسار، بالبحث في قائمتَي البوابتين.
     */
    public static function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach ([self::GOV, self::OPS] as $key) {
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
