<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * يحدّد البوابة النشطة داخل لوحة الوزارة وقائمتها الجانبية.
 *
 * أربع بوابات تتشارك التخطيط نفسه: قسم الإحصاء تحت /stats ومعه شاشة العرض تحت
 * /gov، وقسم الخدمات والتراخيص تحت /services، وإدارة النظام ببوابة المعلومات
 * تحت /subadmin، وتطبيق حوات تحت /admin. التمييز من اسم المسار
 * لا من المسار نفسه: مسارات البوابات الثلاث الأولى تحمل بادئاتها، ومسارات
 * تطبيق حوات إمّا بالبادئة panel. أو بلا بادئة (مركز العمليات).
 *
 * تطبيق حوات خلف دخوله وقائمته تتبدّل مع دور المستخدم (config/hawat.php →
 * nav_panel). وإدارة النظام خلف دخولها على /login.
 */
class Nav
{
    public const STATS = 'stats';

    public const SERVICES = 'services';

    public const SUBADMIN = 'subadmin';

    public const OPS = 'ops';

    /**
     * مفتاح كل بوابة ومصدر قائمتها، بترتيب صفحة /sections. تطبيق حوات آخرها
     * لأنه الافتراضي: يلتقط كل مسار لم يطابق بادئة قبله، وقائمته تُبنى في
     * panelSections() لا تُقرأ من مفتاح واحد.
     */
    private const SECTIONS = [
        self::STATS => 'hawat.nav_stats',
        self::SERVICES => 'hawat.nav_services',
        self::SUBADMIN => 'hawat.nav_subadmin',
        self::OPS => 'hawat.nav',
    ];

    /**
     * بادئات أسماء المسارات لكل بوابة. شاشة العرض (gov.) من الإحصاء، وبوابة
     * المعلومات (admin.) من إدارة النظام.
     */
    private const PREFIXES = [
        'gov.' => self::STATS,
        'stats.' => self::STATS,
        'services.' => self::SERVICES,
        'subadmin.' => self::SUBADMIN,
        'admin.' => self::SUBADMIN,
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

        foreach (self::PREFIXES as $prefix => $key) {
            if (str_starts_with($route, $prefix)) {
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
     * البوابات الأخرى غير النشطة.
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
     * هل الطلب على لوحة من لوحات شاشة العرض (/gov)؟
     */
    public static function onScreens(): bool
    {
        return str_starts_with(Route::currentRouteName() ?? '', 'gov.');
    }

    /**
     * وضع العرض: تُطوى القائمة الجانبية والشريط العلوي ويُكبَّر القياس.
     *
     * لوحات شاشة العرض تُفتح عليه افتراضًا لأنها تُعرض على شاشة قاعة، وبقية
     * الصفحات لا تدخله إلا بطلبه في ?screen=1. و?screen=0 يعيد التخطيط الكامل.
     */
    public static function screenMode(): bool
    {
        return request()->boolean('screen', self::onScreens());
    }

    /**
     * وجهة زرّ الرجوع في وضع العرض: شاشة الاختيار للوحات القاعة، ورئيسة
     * البوابة لغيرها.
     */
    public static function screenHome(): string
    {
        return self::onScreens() ? 'gov.home' : self::portal()['home'];
    }

    /**
     * لوحات شاشة العرض — مربّعات شاشة الاختيار على /gov.
     */
    public static function screens(): array
    {
        return config('hawat.nav_gov');
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

        return array_map(fn (array $section) => [
            'title' => $section['title'],
            'items' => array_map(self::expand(...), $section['items']),
        ], config(self::SECTIONS[$key]));
    }

    /**
     * رابط عنصر من القائمة.
     */
    public static function url(array $item): string
    {
        return route($item['route'], $item['params'] ?? []);
    }

    /**
     * هل عنصر القائمة هو الصفحة الحالية؟ 'active' نمطٌ يجمع صفحات تحت تبويب
     * واحد (كلوحات شاشة العرض)، و'params' تميّز تبويبات المسار الواحد.
     */
    public static function isActive(array $item): bool
    {
        if (! request()->routeIs($item['active'] ?? $item['route'])) {
            return false;
        }

        foreach ($item['params'] ?? [] as $name => $value) {
            if (request()->route($name) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * عنصر بـ 'tab' تبويبٌ من بوابة المعلومات: اسمه وأيقونته من config/info.php
     * ورابطه مساره هناك.
     */
    private static function expand(array $item): array
    {
        if (! isset($item['tab'])) {
            return $item;
        }

        $tab = config('info.tabs.'.$item['tab']);

        return $item + [
            'label' => $tab['label'],
            'icon' => $tab['icon'],
            'route' => 'admin.tab',
            'params' => ['tab' => $item['tab']],
        ];
    }

    /**
     * دور التطبيق الذي تُبنى عليه قائمة تطبيق حوات. الزائر (كصفحة اختيار
     * البوابات) يرى قائمة المدير العام لأنها الأشمل.
     */
    public static function panelRole(): string
    {
        return Auth::user()?->app_role_key ?? Role::SUPER_ADMIN;
    }

    /**
     * قائمة تطبيق حوات لدور بعينه: أقسام الدور من nav_panel، ويُلحق بالمدير
     * العام مركز العمليات (nav) لأنه قسمه.
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
     * عنوان الصفحة المقابل لاسم مسار، بالبحث في قوائم البوابات كلها ولوحات
     * شاشة العرض — وفي تطبيق حوات قوائم الأدوار كلها لا قائمة الدور الحالي.
     */
    public static function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $groups = [self::screens()];

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
                    // تبويبات بوابة المعلومات تتشارك مسارًا واحدًا، فلا يُعرف أحدها من اسمه.
                    if ($item['route'] === $routeName && ! isset($item['params'])) {
                        return $item['label'];
                    }
                }
            }
        }

        return null;
    }
}
