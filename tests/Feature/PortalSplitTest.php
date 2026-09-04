<?php

namespace Tests\Feature;

use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * لوحة الوزارة مقسومة إلى خمس بوابات على النطاق الرئيسي: لوحة الحكومة التنفيذية
 * تحت /gov، وقسم الإحصاء تحت /stats، وقسم الإدارة الفرعية تحت /subadmin، وقسم
 * الخدمات والتراخيص تحت /services، والمنصة التشغيلية تحت /admin. هذه الاختبارات
 * تحرس الحدّ بينها — أن كل صفحة تُقدَّم من موضعها الجديد فقط، وأن القائمة الجانبية
 * تتبدّل مع البوابة، وهما ما ينكسر بصمت عند إضافة مسار في المكان الخطأ.
 */
class PortalSplitTest extends TestCase
{
    use RefreshDatabase;

    /** المسارات التي تُقدَّم من لوحة الحكومة. */
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
    public function test_a_government_page_answers_under_the_gov_prefix(string $url): void
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

    /** المسارات التي تُقدَّم من المنصة التشغيلية تحت /admin. */
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
        $this->get($url)->assertOk();
    }

    /** المسارات التي تُقدَّم من قسم الإدارة الفرعية تحت /subadmin. */
    public static function subAdministrationPages(): array
    {
        return [
            // رئيسة القسم هي المستخدمون والصلاحيات.
            ['/subadmin'],
            ['/subadmin/org-structure'],
            ['/subadmin/audit-log'],
            ['/subadmin/admin-tasks'],
            ['/subadmin/staff-notifications'],
            ['/subadmin/alerts'],
            ['/subadmin/settings'],
        ];
    }

    #[DataProvider('subAdministrationPages')]
    public function test_a_sub_administration_page_answers_under_its_prefix(string $url): void
    {
        $this->get($url)->assertOk();
    }

    /** المسارات التي تُقدَّم من قسم الخدمات والتراخيص تحت /services. */
    public static function servicesPages(): array
    {
        return [
            // رئيسة القسم هي خدمات الصيادين.
            ['/services'],
            ['/services/my-workspace'],
            ['/services/staff-dashboard'],
            ['/services/staff-management'],
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

    /** مواضع اللوحات قبل استقلال قسمَي الإحصاء والإدارة الفرعية، ووجهة كل منها. */
    public static function sectionRedirects(): array
    {
        return [
            ['/gov/statistics', '/stats'],
            ['/gov/executive-briefing', '/stats'],
            ['/stats/executive-briefing', '/stats'],
            ['/gov/national-indicators', '/stats/national-indicators'],
            ['/gov/annual-bulletin', '/stats/annual-bulletin'],
            ['/admin/markets', '/stats/markets'],
            ['/admin/analytics', '/stats/analytics'],
            ['/gov/alerts', '/subadmin/alerts'],
            ['/admin/audit-log', '/subadmin/audit-log'],
            ['/admin/users', '/subadmin'],
            ['/subadmin/users', '/subadmin'],
            ['/services/fisher-services', '/services'],
            ['/admin/settings', '/subadmin/settings'],
            ['/gov/compliance', '/services/compliance'],
            ['/admin/season-licenses', '/services/season-licenses'],
        ];
    }

    #[DataProvider('sectionRedirects')]
    public function test_a_moved_page_redirects_from_where_it_used_to_live(string $old, string $new): void
    {
        // الروابط المحفوظة قبل النقل تبقى عاملة، ولا تُقدَّم الصفحة من موضعين.
        $this->get($old)->assertMovedPermanently()->assertRedirect($new);
    }

    public function test_the_sections_page_offers_every_portal(): void
    {
        // الجذر يعرض الشعار وحده حتى تكتمل البوابات؛ صفحة الاختيار على /sections.
        $this->get('/sections')
            ->assertOk()
            ->assertSee('التفاعلية', false)
            ->assertSee('الإحصاء', false)
            ->assertSee('الإدارات', false)
            ->assertSee('الخدمات والتراخيص', false)
            ->assertSee('مركز المعلومات', false)
            ->assertSee('href="'.route('gov.home').'"', false)
            ->assertSee('href="'.route('stats.executive-briefing').'"', false)
            ->assertSee('href="'.route('subadmin.users').'"', false)
            ->assertSee('href="'.route('services.fisher-services').'"', false)
            ->assertSee('href="'.route('governorates').'"', false)
            // والسادسة بوابة المعلومات: ليست من بوابات اللوحة، وصندوقها هنا مع ذلك.
            ->assertSee(config('info.title'), false)
            ->assertSee('href="'.route('admin.index').'"', false);
    }

    public function test_each_portal_renders_its_own_sidebar(): void
    {
        // الإنتاج السمكي في لوحة الحكومة، والقوارب في المنصة التشغيلية، والإحصاء
        // الميداني في قسم الإحصاء: كل صفحة ترى روابط بوابتها ولا ترى روابط غيرها.
        // لوحة الحكومة تُفتح على وضع العرض بلا قائمة جانبية، فنطلبها بتخطيطها الكامل.
        $this->get('/gov/production?screen=0')
            ->assertSee(route('gov.sustainability'), false)
            ->assertDontSee(route('boats'), false)
            ->assertDontSee(route('stats.field-statistics'), false);

        $this->get('/admin/boats')
            ->assertSee(route('bycatch'), false)
            ->assertDontSee(route('gov.production'), false)
            ->assertDontSee(route('stats.field-statistics'), false);

        $this->get('/stats/field-statistics')
            ->assertSee(route('stats.reports'), false)
            ->assertDontSee(route('boats'), false)
            ->assertDontSee(route('gov.production'), false);

        $this->get('/subadmin/org-structure')
            ->assertSee(route('subadmin.audit-log'), false)
            ->assertDontSee(route('boats'), false)
            ->assertDontSee(route('gov.production'), false)
            ->assertDontSee(route('stats.field-statistics'), false);

        $this->get('/services')
            ->assertSee(route('services.support'), false)
            ->assertDontSee(route('boats'), false)
            ->assertDontSee(route('gov.production'), false)
            ->assertDontSee(route('subadmin.audit-log'), false);
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
        foreach (Nav::keys() as $portal) {
            foreach (Nav::sections($portal) as $section) {
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
