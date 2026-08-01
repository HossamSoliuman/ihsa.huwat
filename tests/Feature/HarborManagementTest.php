<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Governorate;
use App\Models\HarborLicense;
use App\Models\HarborViolation;
use App\Models\HarborWorker;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HarborManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_harbor_roles_can_open_the_workspace(): void
    {
        $role = Role::query()->where('code', 'stat_employee')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('dashboard.harbors.index'))->assertForbidden();
    }

    public function test_port_supervisor_is_scoped_to_assigned_harbor(): void
    {
        $role = Role::query()->where('code', 'port_supervisor')->firstOrFail();
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id, 'port_id' => $port->id]);

        $this->actingAs($user)->get(route('dashboard.harbors.show', $port))->assertOk()->assertSee($port->name);
        $this->actingAs($user)->get(route('dashboard.harbors.show', $otherPort))->assertForbidden();
    }

    public function test_administrator_selects_a_harbor_through_governorate_and_city(): void
    {
        $region = Region::factory()->create(['name' => 'محافظة الساحل']);
        $city = Governorate::factory()->for($region)->create(['name' => 'مدينة الموج']);
        $harbor = Port::factory()->for($city, 'governorate')->create(['name' => 'مرفأ الصيادين']);
        Port::factory()->create();

        $this->actingAs($this->administrator())
            ->get(route('dashboard.harbors.index', [
                'region_id' => $region->id,
                'governorate_id' => $city->id,
                'port_id' => $harbor->id,
            ]))
            ->assertOk()
            ->assertViewHas('harbor', fn (Port $selectedHarbor): bool => $selectedHarbor->is($harbor))
            ->assertSeeInOrder(['اختيار المرفأ', 'المحافظة', 'المدينة', 'المرفأ', 'مرفأ الصيادين'])
            ->assertSee('data-harbor-selector', false)
            ->assertSee('data-harbor-submit', false);
    }

    public function test_harbor_selection_must_follow_the_selected_geography(): void
    {
        $selectedRegion = Region::factory()->create();
        $selectedCity = Governorate::factory()->for($selectedRegion)->create();
        $otherRegion = Region::factory()->create();
        $otherCity = Governorate::factory()->for($otherRegion)->create();
        $otherHarbor = Port::factory()->for($otherCity, 'governorate')->create();

        $this->actingAs($this->administrator())
            ->from(route('dashboard.harbors.index'))
            ->get(route('dashboard.harbors.index', [
                'region_id' => $selectedRegion->id,
                'governorate_id' => $selectedCity->id,
                'port_id' => $otherHarbor->id,
            ]))
            ->assertRedirect(route('dashboard.harbors.index'))
            ->assertSessionHasErrors('port_id');
    }

    public function test_administrator_is_prompted_to_select_when_multiple_harbors_are_available(): void
    {
        Port::factory()->count(2)->create();

        $this->actingAs($this->administrator())
            ->get(route('dashboard.harbors.index'))
            ->assertOk()
            ->assertViewHas('harbor', null)
            ->assertSee('اختر المرفأ المطلوب');
    }

    public function test_super_administrator_can_render_every_harbor_tab(): void
    {
        $user = $this->administrator();
        $port = Port::factory()->create();
        Boat::factory()->create(['home_port_id' => $port->id]);
        HarborWorker::factory()->create(['port_id' => $port->id]);
        HarborLicense::factory()->create(['port_id' => $port->id]);
        HarborViolation::factory()->create(['port_id' => $port->id, 'created_by' => $user->id]);

        foreach (['overview', 'boats', 'workers', 'licenses', 'violations'] as $tab) {
            $this->actingAs($user)
                ->get(route('dashboard.harbors.show', ['port' => $port, 'tab' => $tab]))
                ->assertOk()
                ->assertSee($port->name);
        }
    }

    public function test_capacity_updates_are_upserted_atomically(): void
    {
        $user = $this->administrator();
        $port = Port::factory()->create();
        $capacities = [
            'large' => ['capacity' => 20, 'status' => 'available'],
            'small' => ['capacity' => 35, 'status' => 'available'],
            'recreational' => ['capacity' => 5, 'status' => 'stopped'],
        ];

        $this->actingAs($user)
            ->put(route('dashboard.harbors.capacities.update', $port), compact('capacities'))
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $port->capacities()->count());
        $this->assertSame(35, $port->capacities()->where('boat_type', 'small')->value('capacity'));
    }

    public function test_worker_identity_is_hashed_and_not_replaced_by_an_empty_update(): void
    {
        $user = $this->administrator();
        $port = Port::factory()->create();
        $payload = [
            'employee_name' => 'عامل المرفأ', 'identity_number' => '1234567890', 'nationality' => 'saudi',
            'worker_type' => 'fisherman', 'mobile_number' => '0500000000', 'employment_status' => 'active',
            'start_date' => today()->format('Y-m-d'),
        ];

        $this->actingAs($user)->post(route('dashboard.harbors.workers.store', $port), $payload)->assertSessionHasNoErrors();
        $worker = HarborWorker::query()->where('employee_name', 'عامل المرفأ')->firstOrFail();
        $this->assertTrue(Hash::check('1234567890', $worker->identity_number));
        $originalHash = $worker->identity_number;

        $this->actingAs($user)->put(route('dashboard.harbors.workers.update', [$port, $worker]), [...$payload, 'identity_number' => null, 'employment_status' => 'suspended'])->assertSessionHasNoErrors();
        $this->assertSame($originalHash, $worker->fresh()->identity_number);
        $this->assertSame('suspended', $worker->fresh()->employment_status);
    }

    public function test_license_attachment_is_private_downloadable_and_removed_with_record(): void
    {
        Storage::fake('local');
        $user = $this->administrator();
        $port = Port::factory()->create();
        $payload = [
            'license_number' => 'LIC-PRIVATE-1', 'license_type' => 'operational',
            'license_holder_name' => 'صاحب الرخصة', 'issue_date' => today()->format('Y-m-d'),
            'expiry_date' => today()->addYear()->format('Y-m-d'), 'license_status' => 'valid',
            'attachment' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($user)->post(route('dashboard.harbors.licenses.store', $port), $payload)->assertSessionHasNoErrors();
        $license = HarborLicense::query()->where('license_number', 'LIC-PRIVATE-1')->firstOrFail();
        Storage::disk('local')->assertExists($license->attachment_path);
        $this->actingAs($user)->get(route('dashboard.harbors.licenses.attachment', [$port, $license]))->assertOk()->assertDownload();

        $path = $license->attachment_path;
        $this->actingAs($user)->delete(route('dashboard.harbors.licenses.destroy', [$port, $license]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
        $this->assertModelMissing($license);
    }

    public function test_violation_rejects_a_boat_from_another_harbor(): void
    {
        $user = $this->administrator();
        $port = Port::factory()->create();
        $otherBoat = Boat::factory()->create();

        $this->actingAs($user)->post(route('dashboard.harbors.violations.store', $port), [
            'violation_number' => 'VIO-SCOPE-1', 'violation_type' => 'تشغيل',
            'violation_date' => now()->format('Y-m-d H:i:s'), 'boat_id' => $otherBoat->id,
            'fine_amount' => 100, 'violation_status' => 'open',
        ])->assertSessionHasErrors('boat_id');

        $this->assertDatabaseMissing('harbor_violations', ['violation_number' => 'VIO-SCOPE-1']);
    }

    public function test_violation_can_be_recorded_for_local_boat_and_harbor_can_be_exported(): void
    {
        $user = $this->administrator();
        $port = Port::factory()->create();
        $boat = Boat::factory()->create(['home_port_id' => $port->id]);

        $this->actingAs($user)->post(route('dashboard.harbors.violations.store', $port), [
            'violation_number' => 'VIO-LOCAL-1', 'violation_type' => 'سلامة',
            'violation_description' => 'مخالفة اشتراطات السلامة.',
            'violation_date' => now()->format('Y-m-d H:i:s'), 'boat_id' => $boat->id,
            'boat_owner_name' => 'مالك القارب', 'fine_amount' => 750, 'violation_status' => 'open',
        ])->assertSessionHasNoErrors();

        $violation = HarborViolation::query()->where('violation_number', 'VIO-LOCAL-1')->firstOrFail();
        $this->assertSame($user->id, $violation->created_by);
        $this->assertSame($port->id, $violation->port_id);

        $this->actingAs($user)
            ->get(route('dashboard.harbors.export', $port))
            ->assertOk()
            ->assertDownload();
    }

    public function test_region_manager_can_only_create_harbor_inside_assigned_region(): void
    {
        $role = Role::query()->where('code', 'region_manager')->firstOrFail();
        $region = Region::factory()->create();
        $otherRegion = Region::factory()->create();
        $governorate = Governorate::factory()->create(['region_id' => $region->id]);
        $otherGovernorate = Governorate::factory()->create(['region_id' => $otherRegion->id]);
        $manager = User::factory()->create(['role_id' => $role->id, 'region_id' => $region->id]);

        $this->actingAs($manager)->post(route('dashboard.harbors.store'), ['name' => 'مرفأ مسموح', 'governorate_id' => $governorate->id, 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($manager)->post(route('dashboard.harbors.store'), ['name' => 'مرفأ مرفوض', 'governorate_id' => $otherGovernorate->id, 'is_active' => '1'])->assertForbidden();

        $this->assertDatabaseHas('ports', ['name' => 'مرفأ مسموح']);
        $this->assertDatabaseMissing('ports', ['name' => 'مرفأ مرفوض']);
    }

    private function administrator(): User
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
