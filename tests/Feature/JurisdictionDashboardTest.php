<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JurisdictionDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_region_manager_only_sees_their_region_dashboard_data(): void
    {
        $region = Region::factory()->create(['name' => 'Scoped Region']);
        $otherRegion = Region::factory()->create(['name' => 'Hidden Region']);
        $port = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $region->id]), 'name' => 'Scoped Port']);
        Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $otherRegion->id]), 'name' => 'Hidden Port']);

        $this->actingAs($this->user('region_manager', ['region_id' => $region->id]))
            ->get(route('dashboard.region-overview.index', ['region_id' => $otherRegion->id]))
            ->assertOk()->assertSee($port->name)->assertDontSee('Hidden Port');
    }

    public function test_governorate_supervisor_only_sees_their_governorate(): void
    {
        $governorate = Governorate::factory()->create(['name' => 'Scoped Governorate']);
        $other = Governorate::factory()->create(['name' => 'Hidden Governorate']);
        $port = Port::factory()->create(['governorate_id' => $governorate->id, 'name' => 'Visible Port']);
        Port::factory()->create(['governorate_id' => $other->id, 'name' => 'Invisible Port']);

        $this->actingAs($this->user('gov_supervisor', ['governorate_id' => $governorate->id]))
            ->get(route('dashboard.governorate-overview.index', ['governorate_id' => $other->id]))
            ->assertOk()->assertSee($port->name)->assertDontSee('Invisible Port');
    }

    public function test_port_supervisor_can_assign_an_available_employee_to_arrived_trip(): void
    {
        $port = Port::factory()->create();
        $user = $this->user('port_supervisor', ['port_id' => $port->id]);
        $employee = Employee::factory()->create();
        $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'shift_id' => $assignment->shift_id, 'attendance_date' => today(), 'status' => 'present']);
        $trip = Trip::factory()->create(['port_id' => $port->id, 'status' => 'arrived', 'actual_arrival' => now()]);

        $this->actingAs($user)->patch(route('dashboard.port-operations.trips.assignment', [$port, $trip]), ['employee_id' => $employee->id])
            ->assertRedirect(route('dashboard.port-operations.index', ['port_id' => $port->id]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'assigned_employee_id' => $employee->id, 'status' => 'counting']);
    }

    public function test_port_supervisor_cannot_modify_another_port_trip(): void
    {
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $trip = Trip::factory()->create(['port_id' => $otherPort->id, 'status' => 'arrived']);
        $employee = Employee::factory()->create();

        $this->actingAs($this->user('port_supervisor', ['port_id' => $port->id]))
            ->patch(route('dashboard.port-operations.trips.assignment', [$otherPort, $trip]), ['employee_id' => $employee->id])
            ->assertForbidden();
    }

    public function test_port_supervisor_can_approve_a_pending_discrepancy_atomically(): void
    {
        $port = Port::factory()->create();
        $user = $this->user('port_supervisor', ['port_id' => $port->id]);
        $trip = Trip::factory()->create(['port_id' => $port->id, 'status' => 'pending_review', 'actual_arrival' => now()]);
        $discrepancy = TripDiscrepancy::factory()->create(['trip_id' => $trip->id, 'review_status' => 'pending']);

        $this->actingAs($user)->patch(route('dashboard.port-operations.discrepancies.approve', [$port, $discrepancy]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trip_discrepancies', ['id' => $discrepancy->id, 'review_status' => 'approved', 'reviewed_by' => $user->id]);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'status' => 'approved', 'approved_by' => $user->id]);
    }

    public function test_employee_cannot_be_assigned_to_two_ports_on_the_same_day(): void
    {
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $employee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $otherPort->id, 'assignment_date' => today()]);
        $user = $this->user('port_supervisor', ['port_id' => $port->id]);

        $this->actingAs($user)->post(route('dashboard.port-operations.assignments.store', $port), [
            'employee_id' => $employee->id,
            'shift_id' => EmployeeAssignment::query()->firstOrFail()->shift_id,
        ])->assertSessionHasErrors('employee_id');

        $this->assertDatabaseCount('employee_assignments', 1);
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role_id' => Role::query()->where('code', $role)->value('id')]);
    }
}
