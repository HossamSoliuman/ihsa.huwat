<?php

namespace Tests\Feature;

use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * لوحة الوزارة مقسومة إلى بوابتين على النطاق الرئيسي: لوحة الحكومة التنفيذية
 * تحت /gov، والمنصة التشغيلية على المسارات العليا. هذه الاختبارات تحرس الحدّ
 * بينهما — أن كل صفحة تُقدَّم من موضعها الجديد فقط، وأن القائمة الجانبية
 * تتبدّل مع البوابة، وهما ما ينكسر بصمت عند إضافة مسار في المكان الخطأ.
 */
class PortalSplitTest extends TestCase
{
    use RefreshDatabase;

    /** المسارات الخمسة عشر التي تُقدَّم من لوحة الحكومة. */
    public static function govPages(): array
    {
        return [
            ['/gov'],
            ['/gov/national-indicators'],
            ['/gov/sea-map'],
            ['/gov/production'],
            ['/gov/ports-compare'],
            ['/gov/field-statistics'],
            ['/gov/approved-catch'],
            ['/gov/sustainability'],
            ['/gov/compliance'],
            ['/gov/food-security'],
            ['/gov/ai-assistant'],
            ['/gov/alerts'],
            ['/gov/reports'],
            ['/gov/monthly-reports'],
            ['/gov/annual-bulletin'],
        ];
    }

    #[DataProvider('govPages')]
    public function test_a_government_page_answers_under_the_gov_prefix(string $url): void
    {
        $this->get($url)->assertOk();
    }

    /** المواضع التي كانت تُقدَّم منها هذه الصفحات قبل النقل. */
    public static function vacatedPaths(): array
    {
        return [['/production'], ['/sea-map'], ['/compliance'], ['/alerts'], ['/national-indicators'], ['/reports']];
    }

    #[DataProvider('vacatedPaths')]
    public function test_a_moved_page_no_longer_answers_at_its_old_path(string $url): void
    {
        $this->get($url)->assertNotFound();
    }

    public function test_the_operations_console_keeps_its_top_level_paths(): void
    {
        foreach (['/governorates', '/boats', '/ports', '/markets', '/settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_root_offers_both_portals(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('لوحة الحكومة', false)
            ->assertSee('المنصة التشغيلية', false)
            ->assertSee('href="'.route('gov.home').'"', false)
            ->assertSee('href="'.route('governorates').'"', false);
    }

    public function test_each_portal_renders_its_own_sidebar(): void
    {
        // الإنتاج السمكي في لوحة الحكومة، والقوارب في المنصة التشغيلية:
        // كل صفحة ترى روابط بوابتها ولا ترى روابط الأخرى.
        $this->get('/gov/production')
            ->assertSee(route('gov.field-statistics'), false)
            ->assertDontSee(route('boats'), false);

        $this->get('/boats')
            ->assertSee(route('markets'), false)
            ->assertDontSee(route('gov.field-statistics'), false);
    }

    public function test_the_sidebar_links_resolve_for_every_navigation_entry(): void
    {
        // رابط بمسار غير مسجَّل يرمي استثناءً عند العرض، فنتحقق منها جميعًا مقدمًا.
        foreach ([Nav::GOV, Nav::OPS] as $portal) {
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
