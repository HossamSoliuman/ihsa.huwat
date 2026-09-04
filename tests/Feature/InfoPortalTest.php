<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بوابة المعلومات تعمل على مضيف مستقل فوق نفس قاعدة البيانات التي تقرأ منها
 * لوحة الوزارة، وخلف تسجيل دخول لأنها تحرّر البيانات الأساسية. هذه الاختبارات
 * تحرس الفصل بين المضيفين، والباب المغلق أمام الزائر، وتحرير الحقول المرتبطة
 * بمفاتيح أجنبية — وهي المواضع التي تنكسر بصمت عند تغيير المخطط.
 */
class InfoPortalTest extends TestCase
{
    use RefreshDatabase;

    private const PORTAL = 'http://info.hawat.test';

    private const MINISTRY = 'http://hawat.test';

    /**
     * البوابة كلها خلف الدخول، فكل اختبار يمسّ محتواها يبدأ بمستخدم داخلٍ إليها.
     */
    private function signIn(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    private function governorate(): Governorate
    {
        $region = Region::create(['name' => 'المنطقة الشرقية', 'code' => 'EST']);

        return Governorate::create([
            'region_id' => $region->id,
            'name' => 'القطيف',
            'code' => 'QTF',
        ]);
    }

    public function test_the_portal_answers_on_its_own_host(): void
    {
        $this->signIn();

        $this->get(self::PORTAL.'/')->assertOk();
        $this->get(self::PORTAL.'/admin/geo')->assertOk();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(self::PORTAL.'/')->assertRedirect(route('login'));
        $this->get(self::PORTAL.'/admin/geo')->assertRedirect(route('login'));

        // والكتابة محجوبة كالقراءة: لا يكفي إخفاء الصفحة عن الزائر.
        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء زائر',
            'status' => 'نشط',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('ports', 0);
    }

    public function test_the_login_page_is_open_to_a_guest(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(config('info.title'), false);
    }

    public function test_a_known_user_signs_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'كلمة-سر-الاختبار']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'كلمة-سر-الاختبار'])
            ->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $user = User::factory()->create(['password' => 'كلمة-سر-الاختبار']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'كلمة-أخرى'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_unknown_tab_is_not_found_rather_than_an_error(): void
    {
        $this->signIn();

        $this->get(self::PORTAL.'/admin/no-such-tab')->assertNotFound();
    }

    public function test_the_ministry_dashboard_answers_on_the_main_host(): void
    {
        $this->get(self::MINISTRY.'/')->assertOk();
        $this->get(self::MINISTRY.'/gov')->assertOk();
    }

    public function test_the_two_portals_do_not_leak_onto_each_other(): void
    {
        $this->signIn();

        // صفحة من لوحة الحكومة وأخرى من المنصة التشغيلية — كلتاهما محجوبتان عن مضيف البوابة.
        $this->get(self::PORTAL.'/gov/production')->assertNotFound();
        $this->get(self::PORTAL.'/boats')->assertNotFound();
        $this->get(self::MINISTRY.'/admin/geo')->assertNotFound();
    }

    public function test_a_record_is_created_through_a_relation_backed_select(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء القطيف',
            'code' => 'PQTF',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertRedirect();

        $this->assertDatabaseHas('ports', [
            'name' => 'ميناء القطيف',
            'governorate_id' => $governorate->id,
        ]);
    }

    public function test_a_relation_value_outside_the_option_list_is_rejected(): void
    {
        $this->signIn();
        $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء وهمي',
            'governorate_id' => 4321,
            'status' => 'نشط',
        ])->assertSessionHasErrors('governorate_id');

        $this->assertDatabaseCount('ports', 0);
    }

    public function test_a_status_outside_the_option_list_is_rejected(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء القطيف',
            'governorate_id' => $governorate->id,
            'status' => 'حالة غير معرّفة',
        ])->assertSessionHasErrors('status');
    }

    public function test_a_record_is_updated_and_deleted(): void
    {
        $this->signIn();
        $governorate = $this->governorate();
        $port = Port::create(['name' => 'ميناء دارين', 'governorate_id' => $governorate->id]);

        $this->put(route('admin.resource.update', ['tab' => 'geo', 'resource' => 'ports', 'id' => $port->id]), [
            'name' => 'ميناء دارين الجديد',
            'governorate_id' => $governorate->id,
            'status' => 'صيانة',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('ports', ['id' => $port->id, 'name' => 'ميناء دارين الجديد', 'status' => 'صيانة']);

        $this->delete(route('admin.resource.destroy', ['tab' => 'geo', 'resource' => 'ports', 'id' => $port->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('ports', ['id' => $port->id]);
    }

    public function test_every_write_lands_in_the_audit_log_under_its_author(): void
    {
        $user = $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء الدمام',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertSessionHasNoErrors();

        // الدخول ليس حراسةً فحسب: هو ما يجعل للسجل صاحبًا معروفًا.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'إنشاء',
            'entity' => 'Port',
            'user_email' => $user->email,
        ]);
    }

    public function test_the_audit_log_refuses_writes(): void
    {
        $this->signIn();

        $this->post(route('admin.resource.store', ['tab' => 'audit', 'resource' => 'audit-logs']), [])
            ->assertForbidden();

        $log = AuditLog::create(['action' => 'اختبار', 'entity' => 'System']);

        $this->delete(route('admin.resource.destroy', ['tab' => 'audit', 'resource' => 'audit-logs', 'id' => $log->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }
}
