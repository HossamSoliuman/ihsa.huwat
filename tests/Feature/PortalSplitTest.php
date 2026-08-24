<?php

namespace Tests\Feature;

use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * لوحة الوزارة مقسومة إلى ثلاث بوابات على النطاق الرئيسي: لوحة الحكومة التنفيذية
 * تحت /gov، وقسم الإحصاء تحت /stats، والمنصة التشغيلية تحت /admin. هذه الاختبارات
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
            ['/gov/sea-map'],
            ['/gov/production'],
            ['/gov/ports-compare'],
            ['/gov/sustainability'],
            ['/gov/compliance'],
            ['/gov/alerts'],
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
            ['/stats'],
            ['/stats/executive-briefing'],
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
            ['/admin/season-licenses'],
            ['/admin/boats'],
            ['/admin/fishers'],
            ['/admin/trips'],
            ['/admin/boat-timeline'],
            ['/admin/ports'],
            ['/admin/fishing-sites'],
            ['/admin/discrepancy-review'],
            ['/admin/bycatch'],
            ['/admin/audit-log'],
            ['/admin/users'],
            ['/admin/settings'],
        ];
    }

    #[DataProvider('opsPages')]
    public function test_an_operations_page_answers_under_the_admin_prefix(string $url): void
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

    /** مواضع لوحات قسم الإحصاء قبل استقلاله ببوابته، ووجهة كل منها. */
    public static function statisticsRedirects(): array
    {
        return [
            ['/gov/statistics', '/stats'],
            ['/gov/national-indicators', '/stats/national-indicators'],
            ['/gov/annual-bulletin', '/stats/annual-bulletin'],
            ['/admin/markets', '/stats/markets'],
            ['/admin/analytics', '/stats/analytics'],
        ];
    }

    #[DataProvider('statisticsRedirects')]
    public function test_a_statistics_page_redirects_from_where_it_used_to_live(string $old, string $new): void
    {
        // الروابط المحفوظة قبل النقل تبقى عاملة، ولا تُقدَّم الصفحة من موضعين.
        $this->get($old)->assertMovedPermanently()->assertRedirect($new);
    }

    public function test_the_root_offers_the_three_portals(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('لوحة الحكومة', false)
            ->assertSee('قسم الإحصاء', false)
            ->assertSee('المنصة التشغيلية', false)
            ->assertSee('href="'.route('gov.home').'"', false)
            ->assertSee('href="'.route('stats.home').'"', false)
            ->assertSee('href="'.route('governorates').'"', false);
    }

    public function test_each_portal_renders_its_own_sidebar(): void
    {
        // الإنتاج السمكي في لوحة الحكومة، والقوارب في المنصة التشغيلية، والإحصاء
        // الميداني في قسم الإحصاء: كل صفحة ترى روابط بوابتها ولا ترى روابط غيرها.
        $this->get('/gov/production')
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
    }

    public function test_the_statistics_tabs_are_gone_from_the_other_portals(): void
    {
        // اللوحة الواحدة في بوابة واحدة: لو عاد تبويب من تبويبات القسم إلى قائمة
        // لوحة الحكومة أو المنصة التشغيلية لظهر رابطه هنا.
        foreach ([Nav::GOV, Nav::OPS] as $portal) {
            foreach (Nav::sections($portal) as $section) {
                foreach ($section['items'] as $item) {
                    $this->assertStringStartsNotWith(
                        'stats.',
                        $item['route'],
                        "تبويب من قسم الإحصاء ما زال في قائمة بوابة أخرى: {$item['route']}"
                    );
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
