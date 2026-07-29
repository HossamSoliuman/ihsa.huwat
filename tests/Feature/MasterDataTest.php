<?php

namespace Tests\Feature;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_super_administrators_can_manage_master_data(): void
    {
        $hrRole = Role::query()->where('code', 'hr_manager')->firstOrFail();
        $hrManager = User::factory()->create(['role_id' => $hrRole->id]);

        $this->actingAs($hrManager)->get(route('dashboard.master-data.index'))->assertForbidden();
    }

    public function test_each_master_data_section_renders_for_super_administrator(): void
    {
        $administrator = $this->administrator();
        $region = Region::factory()->create();
        $governorate = Governorate::factory()->create(['region_id' => $region->id]);
        Port::factory()->create(['governorate_id' => $governorate->id]);

        foreach (['regions', 'governorates', 'ports', 'boats', 'captains', 'species'] as $section) {
            $this->actingAs($administrator)
                ->get(route('dashboard.master-data.index', ['section' => $section]))
                ->assertOk()
                ->assertSee('البيانات الأساسية');
        }
    }

    public function test_administrator_can_create_reference_records_with_validated_requests(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->post(route('dashboard.regions.store'), ['name' => 'منطقة الاختبار'])->assertSessionHasNoErrors();
        $region = Region::query()->where('name', 'منطقة الاختبار')->firstOrFail();

        $this->actingAs($administrator)->post(route('dashboard.governorates.store'), ['region_id' => $region->id, 'name' => 'محافظة الاختبار'])->assertSessionHasNoErrors();
        $governorate = Governorate::query()->where('name', 'محافظة الاختبار')->firstOrFail();

        $this->actingAs($administrator)->post(route('dashboard.ports.store'), [
            'governorate_id' => $governorate->id,
            'name' => 'ميناء الاختبار',
            'location_name' => 'الساحل',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $port = Port::query()->where('name', 'ميناء الاختبار')->firstOrFail();

        $this->actingAs($administrator)->post(route('dashboard.boats.store'), [
            'name' => 'قارب الاختبار',
            'registration_no' => 'BOAT-TEST-1',
            'boat_type' => 'small',
            'harbor_status' => 'occupied',
            'home_port_id' => $port->id,
        ])->assertSessionHasNoErrors();
        $this->actingAs($administrator)->post(route('dashboard.captains.store'), [
            'full_name' => 'كابتن الاختبار',
            'national_id' => '1234567890',
            'phone' => '0500000000',
        ])->assertSessionHasNoErrors();
        $this->actingAs($administrator)->post(route('dashboard.species.store'), ['name_ar' => 'سمك الاختبار'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('boats', ['registration_no' => 'BOAT-TEST-1']);
        $this->assertDatabaseHas('captains', ['full_name' => 'كابتن الاختبار']);
        $this->assertDatabaseHas('fish_species', ['name_ar' => 'سمك الاختبار']);
    }

    public function test_port_status_can_be_toggled(): void
    {
        $administrator = $this->administrator();
        $port = Port::factory()->create(['is_active' => true]);

        $this->actingAs($administrator)
            ->patch(route('dashboard.ports.toggle', $port))
            ->assertRedirect(route('dashboard.master-data.index', ['section' => 'ports']));

        $this->assertFalse($port->fresh()->is_active);
    }

    public function test_linked_master_data_cannot_be_deleted(): void
    {
        $administrator = $this->administrator();
        $region = Region::factory()->create();
        Governorate::factory()->create(['region_id' => $region->id]);

        $this->actingAs($administrator)
            ->from(route('dashboard.master-data.index', ['section' => 'regions']))
            ->delete(route('dashboard.regions.destroy', $region))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('regions', ['id' => $region->id]);
    }

    private function administrator(): User
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
