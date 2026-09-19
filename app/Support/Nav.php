<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * يحدّد البوابة النشطة داخل لوحة الوزارة وقائمتها الجانبية.
 *
 * اللوحة مقسومة إلى خمس بوابات تتشارك نفس التخطيط: لوحة الحكومة تحت /gov،
 * وقسم الإحصاء تحت /stats، وقسم الإدارة الفرعية تحت /subadmin، وقسم الخدمات
 * والتراخيص تحت /services، ولوحة الإدارة تحت /admin. التمييز من اسم المسار
 * لا من المسار نفسه: مسارات البوابات الأربع الأولى تحمل بادئاتها، ومسارات
 * لوحة الإدارة إمّا بالبادئة panel. أو بلا بادئة (مركز المعلومات القديم).
 *
 * لوحة الإدارة وحدها خلف تسجيل الدخول وقائمتها تتبدّل مع دور المستخدم
 * (config/hawat.php → nav_panel): المدير العام يرى حسابات التطبيق ومعها
 * مركز المعلومات كاملًا، والمالك يرى قوائمه هو، وهكذا.
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
     * مفتاح كل بوابة ومصدر قائمتها. لوحة الإدارة آخرها لأنها الافتراضية:
     * أسماء مساراتها بلا بادئة أو بالبادئة panel.، فتلتقط كل ما لم يطابق
     * بادئة قبلها. قائمتها تُبنى في panelSections() لا تُقرأ من مفتاح واحد.
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
        $key ??= self::portalKey();

        if ($key === self::OPS) {
            return self::panelSections();
        }

        return config(self::SECTIONS[$key]);
    }

    /**
     * دور التطبيق الذي تُبنى عليه قائمة لوحة الإدارة. الزائر (كصفحة اختيار
     * البوابات) يرى قائمة المدير العام لأنها الأشمل.
     */
    public static function panelRole(): string
    {
        return Auth::user()?->app_role_key ?? Role::SUPER_ADMIN;
    }

    /**
     * قائمة لوحة الإدارة لدور بعينه: أقسام الدور من nav_panel، ويُلحق بالمدير
     * العام مركز المعلومات التشغيلي كاملًا (nav) لأنه صار قسمه.
     */
    public static function panelSections(?string $role = null): array
    {
        $role ??= self::panelRole();

        $sections = config('hawat.nav_panel.'.$role, []);

        if ($role === Role::SUPER_ADMIN) {
            $sections = array_merge($sections, config(self::SECTIONS[self::OPS]));
        }

        return $sections;
    }

    /**
     * عنوان الصفحة المقابل لاسم مسار، بالبحث في قوائم البوابات كلها — وفي
     * لوحة الإدارة قوائم الأدوار كلها لا قائمة الدور الحالي وحدها.
     */
    public static function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $groups = [];

        foreach (self::keys() as $key) {
            if ($key === self::OPS) {
                foreach (array_keys(config('hawat.nav_panel', [])) as $role) {
                    $groups[] = self::panelSections($role);
                }

                continue;
            }

            $groups[] = self::sections($key);
        }

        foreach ($groups as $sections) {
            foreach ($sections as $section) {
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
