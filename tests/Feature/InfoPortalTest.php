<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بوابة المعلومات تعمل على مضيف مستقل فوق نفس قاعدة البيانات التي تقرأ منها
 * لوحة الوزارة. هذه الاختبارات تحرس الفصل بين المضيفين وتحرير الحقول المرتبطة
 * بمفاتيح أجنبية، وهما الموضعان اللذان ينكسران بصمت عند تغيير المخطط.
 */
class InfoPortalTest extends TestCase
{
    use RefreshDatabase;

    private const PORTAL = 'http://info.hawat.test';

    private const MINISTRY = 'http://hawat.test';

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
        $this->get(self::PORTAL.'/')->assertOk();
        $this->get(self::PORTAL.'/admin/geo')->assertOk();
    }

    public function test_the_ministry_dashboard_answers_on_the_main_host(): void
    {
        $this->get(self::MINISTRY.'/')->assertOk();
    }

    public function test_the_two_portals_do_not_leak_onto_each_other(): void
    {
        $this->get(self::PORTAL.'/production')->assertNotFound();
        $this->get(self::MINISTRY.'/admin/geo')->assertNotFound();
    }

    public function test_a_record_is_created_through_a_relation_backed_select(): void
    {
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
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء القطيف',
            'governorate_id' => $governorate->id,
            'status' => 'حالة غير معرّفة',
        ])->assertSessionHasErrors('status');
    }

    public function test_a_record_is_updated_and_deleted(): void
    {
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

    public function test_every_write_lands_in_the_audit_log(): void
    {
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء الدمام',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => 'إنشاء', 'entity' => 'Port']);
    }

    public function test_the_audit_log_refuses_writes(): void
    {
        $this->post(route('admin.resource.store', ['tab' => 'audit', 'resource' => 'audit-logs']), [])
            ->assertForbidden();

        $log = AuditLog::create(['action' => 'اختبار', 'entity' => 'System']);

        $this->delete(route('admin.resource.destroy', ['tab' => 'audit', 'resource' => 'audit-logs', 'id' => $log->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }
}
