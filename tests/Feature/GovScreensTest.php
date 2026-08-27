<?php

namespace Tests\Feature;

use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * لوحة الحكومة تُعرض على شاشة قاعة: رئيستها شاشة اختيار بمربّعات، ونقر المربّع
 * يفتح لوحته في وضع العرض (?screen=1) بلا قائمة جانبية ولا شريط علوي. هذه
 * الاختبارات تحرس الطرفين — أن كل تبويب له مربّعه، وأن وضع العرض يطوي التخطيط.
 */
class GovScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_screen_offers_a_box_for_every_other_tab(): void
    {
        $response = $this->get('/gov')->assertOk();

        foreach (Nav::sections(Nav::GOV) as $section) {
            foreach ($section['items'] as $item) {
                if ($item['route'] === 'gov.home') {
                    // شاشة الاختيار لا تعرض مربّعًا لنفسها.
                    continue;
                }

                $response->assertSee($item['label'], false)
                    ->assertSee('href="'.route($item['route'], ['screen' => 1]).'"', false);
            }
        }
    }

    /** لوحات لوحة الحكومة التي تُفتح من مربّعاتها. */
    public static function screenPages(): array
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

    #[DataProvider('screenPages')]
    public function test_a_government_page_opens_in_display_mode_on_its_own(string $url): void
    {
        // الشاشة الكبيرة لا أحد يضبطها عند كل تشغيل: العنوان المجرّد يكفي.
        $this->get($url)
            ->assertOk()
            ->assertSee('class="screen-bar"', false)
            ->assertDontSee('class="sidebar"', false)
            ->assertDontSee('class="topbar"', false);
    }

    #[DataProvider('screenPages')]
    public function test_the_full_layout_comes_back_when_display_mode_is_turned_off(string $url): void
    {
        $this->get($url.'?screen=0')
            ->assertOk()
            ->assertSee('class="sidebar"', false)
            ->assertSee('class="topbar"', false)
            ->assertDontSee('class="screen-bar"', false);
    }

    public function test_only_the_government_portal_opens_in_display_mode(): void
    {
        // بقية البوابات تُدار من مكتب لا من قاعة، فتبقى على تخطيطها الكامل.
        foreach (['/stats', '/subadmin', '/services', '/admin/boats'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="sidebar"', false)
                ->assertDontSee('class="screen-bar"', false);
        }
    }

    public function test_display_mode_returns_to_the_home_screen_without_leaving_it(): void
    {
        // زر الرجوع داخل لوحة يبقى في وضع العرض، وعلى شاشة الاختيار وحدها يخرج منه.
        $this->get('/gov/production')
            ->assertSee('href="'.route('gov.home').'"', false);

        $this->get('/gov')
            ->assertSee('href="'.route('gov.home', ['screen' => 0]).'"', false);
    }
}
