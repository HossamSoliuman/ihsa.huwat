<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * أربع بوابات في منتجين على النطاق الرئيسي: الإحصاء تحت /stats ومعه شاشة العرض
 * تحت /gov، والخدمات والتراخيص تحت /services، وإدارة النظام تحت /subadmin مع
 * بوابة المعلومات على مضيفها، وتطبيق حوات تحت /admin. هذه الاختبارات تحرس الحدّ
 * بينها — أن كل صفحة تُقدَّم من موضعها فقط، وأن القائمة الجانبية تتبدّل مع
 * البوابة، وهما ما ينكسر بصمت عند إضافة مسار في المكان الخطأ.
 *
 * إدارة النظام وتطبيق حوات خلف الدخول، فصفحاتهما تُطلب هنا بمستخدم داخل.
 */
class PortalSplitTest extends TestCase
{
    use RefreshDatabase;

    private function asSuperAdmin(): static
    {
        return $this->actingAs(User::factory()->superAdmin()->create());
    }

    /** لوحات شاشة العرض — تبويب واحد في قائمة الإحصاء. */
    public static function govPages(): array
    {
        return [
            ['/gov'],
            ['/gov/overview'],
            ['/gov/sea-map'],
            ['/gov/production'],
            ['/gov/ports-compare'],
            ['/gov/sustainability'],
        ];
    }

    #[DataProvider('govPages')]
    public function test_a_wall_screen_answers_under_the_gov_prefix(string $url): void
    {
        $this->get($url)->assertOk();
    }

    /** المسارات التي تُقدَّم من قسم الإحصاء تحت /stats. */
    public static function statisticsPages(): array
    {
        return [
            // رئيسة القسم هي موجز الإدارة العليا.
            ['/stats'],
            ['/stats/national-indicators'],
            ['/stats/performance-compare'],
            ['/stats/field-statistics'],
            ['/stats/approved-catch'],
            ['/stats/statistics-officers'],
            ['/stats/catch-trace'],
            ['/stats/analytics'],
            ['/stats/ai-assistant'],
            ['/stats/reports'],
            ['/stats/monthly-reports'],
            ['/stats/annual-bulletin'],
            ['/stats/markets'],
            ['/stats/supply-chain'],
            ['/stats/food-security'],
        ];
    }

    #[DataProvider('statisticsPages')]
    public function test_a_statistics_page_answers_under_the_stats_prefix(string $url): void
    {
        $this->get($url)->assertOk();
    }

    /**
     * صفحات مركز العمليات — قسم المدير العام في تطبيق حوات تحت /admin. البيانات
     * الأساسية خرجت من قائمته إلى إدارة النظام، وصفحاتها باقية على مساراتها.
     */
    public static function opsPages(): array
    {
        return [
            ['/admin/governorates'],
            ['/admin/regions'],
            ['/admin/species'],
            ['/admin/fishing-seasons'],
            ['/admin/boats'],
            ['/admin/fishers'],
            ['/admin/trips'],
            ['/admin/boat-timeline'],
            ['/admin/ports'],
            ['/admin/fishing-sites'],
            ['/admin/discrepancy-review'],
            ['/admin/bycatch'],
        ];
    }

    #[DataProvider('opsPages')]
    public function test_an_operations_page_answers_under_the_admin_prefix(string $url): void
    {
        $this->asSuperAdmin()->get($url)->assertOk();
    }

    #[DataProvider('opsPages')]
    public function test_an_operations_page_is_behind_the_panel_login(string $url): void
    {
        $this->get($url)->assertRedirect(route('panel.login'));
    }

    /** لوحات إدارة النظام على النطاق الرئيسي تحت /subadmin. */
    public static function subAdministrationPages(): array
    {
        return [
            ['/subadmin/org-structure'],
            ['/subadmin/staff-management'],
            ['/subadmin/audit-log'],
            ['/subadmin/admin-tasks'],
            ['/subadmin/staff-notifications'],
            ['/subadmin/alerts'],
            ['/subadmin/settings'],
        ];
    }

    #[DataProvider('subAdministrationPages')]
    public function test_a_system_administration_page_answers_under_its_prefix(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertOk();
    }

    #[DataProvider('subAdministrationPages')]
    public function test_a_system_administration_page_is_behind_the_info_portal_login(string $url): void
    {
        // دخول واحد لنصفَي إدارة النظام: صفحة دخول بوابة المعلومات، لا دخول التطبيق.
        $this->get($url)->assertRedirect(route('login'));
    }

    /** المسارات التي تُقدَّم من قسم الخدمات والتراخيص تحت /services. */
    public static function servicesPages(): array
    {
        return [
            // رئيسة القسم هي خدمات الصيادين.
            ['/services'],
            ['/services/my-workspace'],
            ['/services/staff-dashboard'],
            ['/services/season-licenses'],
            ['/services/compliance'],
            ['/services/support'],
        ];
    }

    #[DataProvider('servicesPages')]
    public function test_a_services_page_answers_under_its_prefix(string $url): void
    {
        $this->get($url)->assertOk();
    }

    /** المواضع التي كانت تُقدَّم منها هذه الصفحات قبل النقل. */
    public static function vacatedPaths(): array
    {
        return [
            ['/production'], ['/sea-map'], ['/compliance'], ['/alerts'], ['/national-indicators'], ['/reports'],
            ['/governorates'], ['/boats'], ['/ports'], ['/markets'], ['/settings'],
        ];
    }

    #[DataProvider('vacatedPaths')]
    public function test_a_moved_page_no_longer_answers_at_its_old_path(string $url): void
    {
        $this->get($url)->assertNotFound();
    }

    /** مواضع اللوحات قبل نقلها، ووجهة كل منها. */
    public static function sectionRedirects(): array
    {
        return [
            ['/gov/statistics', '/stats'],
            ['/gov/executive-briefing', '/stats'],
            ['/stats/executive-briefing', '/stats'],
            ['/gov/national-indicators', '/stats/national-indicators'],
            ['/gov/annual-bulletin', '/stats/annual-bulletin'],
            ['/gov/alerts', '/subadmin/alerts'],
            ['/services/fisher-services', '/services'],
            ['/gov/compliance', '/services/compliance'],
            ['/services/staff-management', '/subadmin/staff-management'],
        ];
    }

    #[DataProvider('sectionRedirects')]
    public function test_a_moved_page_redirects_from_where_it_used_to_live(string $old, string $new): void
    {
        // الروابط المحفوظة قبل النقل تبقى عاملة، ولا تُقدَّم الصفحة من موضعين.
        $this->get($old)->assertMovedPermanently()->assertRedirect($new);
    }

    public function test_the_old_users_page_redirects_to_the_one_that_edits_them(): void
    {
        // صفحة /subadmin/users كانت نسخة للعرض فقط من تبويب بوابة المعلومات.
        $this->get('/subadmin/users')->assertMovedPermanently()->assertRedirect(route('admin.tab', 'permissions'));
    }

    public function test_the_sections_page_offers_four_portals_in_two_products(): void
    {
        $this->get('/sections')
            ->assertOk()
            ->assertSeeInOrder(['الوزارة', 'الإحصاء', 'الخدمات والتراخيص', 'إدارة النظام', 'تطبيق حوات'], false)
            ->assertSee('href="'.route('stats.executive-briefing').'"', false)
            ->assertSee('href="'.route('services.fisher-services').'"', false)
            ->assertSee('href="'.route('admin.index').'"', false)
            ->assertSee('href="'.route('panel.home').'"', false)
            // صندوق التطبيق يصفه بأدواره لا بقائمة دور واحد.
            ->assertSeeInOrder(['المالك', 'الكابتن', 'العدّاد', 'الدلال', 'التاجر'], false)
            // البوابات التي طُويت لا صندوق لها.
            ->assertDontSee('التفاعلية', false)
            ->assertDontSee('الإدارات', false)
            ->assertDontSee('href="'.route('gov.home').'"', false);
    }

    public function test_each_portal_renders_its_own_sidebar(): void
    {
        // كل صفحة ترى روابط بوابتها ولا ترى روابط غيرها. شاشة العرض من الإحصاء،
        // وتُفتح على وضع العرض بلا قائمة جانبية، فنطلبها بتخطيطها الكامل.
        $this->get('/gov/production?screen=0')
            ->assertSee(route('stats.field-statistics'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('services.support'), false);

        $this->get('/stats/field-statistics')
            ->assertSee(route('stats.reports'), false)
            ->assertSee(route('gov.home'), false)
            ->assertDontSee(route('trips'), false);

        $this->get('/services')
            ->assertSee(route('services.support'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('stats.field-statistics'), false)
            ->assertDontSee(route('subadmin.staff-management'), false);

        $this->asSuperAdmin()->get('/admin/trips')
            ->assertSee(route('bycatch'), false)
            ->assertSee(route('panel.users'), false)
            ->assertDontSee(route('gov.production'), false)
            ->assertDontSee(route('stats.field-statistics'), false);

        $this->get('/subadmin/org-structure')
            ->assertSee(route('subadmin.audit-log'), false)
            ->assertSee(route('subadmin.staff-management'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('stats.field-statistics'), false);
    }

    public function test_master_data_is_left_to_system_administration(): void
    {
        // البيانات الأساسية موضعها إدارة النظام وحدها، فلا تعرضها قائمة المدير العام.
        $response = $this->asSuperAdmin()->get('/admin/trips')->assertOk();

        foreach (['regions', 'governorates', 'species', 'fishing-seasons', 'boats', 'fishers', 'ports', 'fishing-sites'] as $route) {
            $response->assertDontSee('href="'.route($route).'"', false);
        }

        $this->get('/subadmin/settings')
            ->assertSee(route('admin.tab', 'geo'), false)
            ->assertSee(route('admin.tab', 'fleet'), false)
            ->assertSee(route('admin.tab', 'seasons'), false);
    }

    public function test_system_administration_shows_one_menu_on_both_hosts(): void
    {
        // نصفا إدارة النظام قائمة واحدة: لوحات /subadmin في بوابة المعلومات،
        // وتبويباتها في /subadmin.
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.tab', 'geo'))
            ->assertOk()
            ->assertSee(route('subadmin.org-structure'), false)
            ->assertSee(route('subadmin.audit-log'), false);

        $this->get(route('subadmin.alerts'))
            ->assertSee(route('admin.tab', 'permissions'), false)
            ->assertSee(route('admin.tab', 'powerbi'), false);
    }

    public function test_every_info_portal_tab_has_its_place_in_the_menu(): void
    {
        // القائمة الجانبية لبوابة المعلومات هي قائمة إدارة النظام، فتبويب لا موضع
        // له فيها لا يصل إليه أحد.
        $listed = collect(Nav::sections(Nav::SUBADMIN))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('tab')
            ->filter()
            ->all();

        $this->assertEqualsCanonicalizing(array_keys(config('info.tabs')), $listed);
    }

    public function test_a_sections_tabs_are_gone_from_the_other_portals(): void
    {
        // اللوحة الواحدة في بوابة واحدة: لو عاد تبويب من تبويبات قسم إلى قائمة
        // بوابة أخرى لظهرت بادئته هنا.
        foreach (Nav::keys() as $portal) {
            foreach (Nav::sections($portal) as $section) {
                foreach ($section['items'] as $item) {
                    foreach ([Nav::STATS, Nav::SUBADMIN, Nav::SERVICES] as $owner) {
                        if ($portal === $owner) {
                            continue;
                        }

                        $this->assertStringStartsNotWith(
                            $owner.'.',
                            $item['route'],
                            "تبويب من قسم {$owner} ما زال في قائمة بوابة أخرى: {$item['route']}"
                        );
                    }
                }
            }
        }
    }

    public function test_the_sidebar_links_resolve_for_every_navigation_entry(): void
    {
        // رابط بمسار غير مسجَّل يرمي استثناءً عند العرض، فنتحقق منها جميعًا مقدمًا.
        foreach ([...Nav::keys(), 'screens'] as $portal) {
            $sections = $portal === 'screens' ? Nav::screens() : Nav::sections($portal);

            foreach ($sections as $section) {
                foreach ($section['items'] as $item) {
                    $this->assertTrue(
                        Route::has($item['route']),
                        "القائمة الجانبية تشير إلى مسار غير مسجَّل: {$item['route']}"
                    );
                }
            }
        }
    }
}
