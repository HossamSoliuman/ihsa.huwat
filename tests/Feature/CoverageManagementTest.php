<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CoverageManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_regional_roles_can_open_coverage_dashboard(): void
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'hr_manager')->value('id')]);

        $this->actingAs($user)->get(route('dashboard.coverage.index'))->assertForbidden();
    }

    public function test_region_manager_only_sees_ports_in_their_region(): void
    {
        $region = Region::factory()->create();
        $otherRegion = Region::factory()->create();
        $port = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $region->id]), 'name' => 'ميناء المنطقة']);
        $otherPort = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $otherRegion->id]), 'name' => 'ميناء مخفي']);
        $user = $this->regionManager($region);

        $this->actingAs($user)->get(route('dashboard.coverage.index'))
            ->assertOk()
            ->assertSee($port->name)
            ->assertDontSee($otherPort->name);
    }

    public function test_dashboard_classifies_uncovered_and_high_load_ports(): void
    {
        $user = $this->superAdmin();
        $uncovered = Port::factory()->create(['name' => 'ميناء بلا تغطية']);
        $busy = Port::factory()->create(['name' => 'ميناء مزدحم']);
        $employee = Employee::factory()->create();
        $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $busy->id]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'shift_id' => $assignment->shift_id, 'status' => 'present']);
        Trip::factory()->count(3)->create(['port_id' => $busy->id, 'status' => 'arrived', 'actual_arrival' => now()]);

        $this->actingAs($user)->get(route('dashboard.coverage.index'))
            ->assertOk()
            ->assertSee($uncovered->name)
            ->assertSee($busy->name)
            ->assertSee('status-uncovered', false)
            ->assertSee('status-high_load', false);
    }

    public function test_port_detail_renders_expected_trips_and_staff(): void
    {
        $user = $this->superAdmin();
        $port = Port::factory()->create();
        $employee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $trip = Trip::factory()->create(['port_id' => $port->id, 'status' => 'expected', 'expected_arrival' => now()->addHour()]);

        $this->actingAs($user)->get(route('dashboard.coverage.index', ['port_detail' => $port->id]))
            ->assertOk()
            ->assertSee($trip->trip_code)
            ->assertSee($employee->user->full_name);
    }

    public function test_region_manager_can_assign_available_employee_inside_region_only(): void
    {
        $region = Region::factory()->create();
        $otherRegion = Region::factory()->create();
        $port = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $region->id])]);
        $otherPort = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $otherRegion->id])]);
        $user = $this->regionManager($region);
        $employee = Employee::factory()->create();
        $shift = Shift::query()->firstOrFail();
        $payload = ['date' => today()->toDateString(), 'employee_id' => $employee->id, 'shift_id' => $shift->id, 'port_id' => $port->id];

        $this->actingAs($user)->post(route('dashboard.coverage.assignments.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_assignments', ['employee_id' => $employee->id, 'port_id' => $port->id, 'is_temporary' => true]);

        $payload['port_id'] = $otherPort->id;
        $payload['employee_id'] = Employee::factory()->create()->id;
        $this->actingAs($user)->post(route('dashboard.coverage.assignments.store'), $payload)->assertSessionHasErrors('port_id');
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', 'super_admin')->value('id')]);
    }

    private function regionManager(Region $region): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('code', 'region_manager')->value('id'),
            'region_id' => $region->id,
        ]);
    }
}
