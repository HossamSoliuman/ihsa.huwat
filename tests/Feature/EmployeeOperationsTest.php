<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\FishSpecies;
use App\Models\Port;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_employee_without_today_assignment_sees_unassigned_state(): void
    {
        [$user] = $this->statEmployee();

        $this->actingAs($user)->get(route('dashboard.employee-operations.index'))
            ->assertOk()->assertSee('لا يوجد تكليف تشغيلي اليوم');
    }

    public function test_dashboard_only_lists_trips_at_today_assigned_port(): void
    {
        [$user, $employee] = $this->statEmployee();
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $visible = Trip::factory()->create(['port_id' => $port->id, 'status' => 'arrived', 'actual_arrival' => now()]);
        $hidden = Trip::factory()->create(['port_id' => $otherPort->id, 'status' => 'arrived', 'actual_arrival' => now()]);

        $this->actingAs($user)->get(route('dashboard.employee-operations.index'))
            ->assertOk()->assertSee($visible->trip_code)->assertDontSee($hidden->trip_code);
    }

    public function test_employee_can_atomically_claim_available_trip_at_assigned_port(): void
    {
        [$user, $employee] = $this->statEmployee();
        $port = Port::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $trip = Trip::factory()->create(['port_id' => $port->id, 'status' => 'waiting_employee', 'actual_arrival' => now()]);

        $this->actingAs($user)->post(route('dashboard.employee-operations.trips.start', $trip))->assertSessionHasNoErrors();

        $this->assertSame('counting', $trip->fresh()->status);
        $this->assertSame($employee->id, $trip->fresh()->assigned_employee_id);
        $this->assertNotNull($trip->fresh()->counting_started_at);
    }

    public function test_employee_cannot_claim_trip_at_another_port(): void
    {
        [$user, $employee] = $this->statEmployee();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => Port::factory()]);
        $trip = Trip::factory()->create(['port_id' => Port::factory(), 'status' => 'arrived', 'actual_arrival' => now()]);

        $this->actingAs($user)->post(route('dashboard.employee-operations.trips.start', $trip))->assertForbidden();
        $this->assertSame('arrived', $trip->fresh()->status);
    }

    public function test_small_difference_auto_approves_trip_and_records_catch(): void
    {
        [$user, $employee] = $this->statEmployee();
        $trip = Trip::factory()->create(['assigned_employee_id' => $employee->id, 'status' => 'counting', 'counting_started_at' => now()]);
        $species = FishSpecies::factory()->create();

        $this->actingAs($user)->put(route('dashboard.employee-operations.trips.catch', $trip), ['catches' => [[
            'species_id' => $species->id, 'reported_kg' => 100, 'verified_kg' => 102, 'boxes_count' => 5,
        ]]])->assertSessionHasNoErrors();

        $this->assertSame('approved', $trip->fresh()->status);
        $this->assertSame('102.00', $trip->fresh()->verified_weight);
        $this->assertNotNull($trip->fresh()->approved_at);
        $this->assertDatabaseHas('catch_details', ['trip_id' => $trip->id, 'species_id' => $species->id, 'boxes_count' => 5]);
        $this->assertDatabaseCount('trip_discrepancies', 0);
    }

    public function test_major_difference_routes_trip_to_review_and_replaces_previous_details(): void
    {
        [$user, $employee] = $this->statEmployee();
        $trip = Trip::factory()->create(['assigned_employee_id' => $employee->id, 'status' => 'counting', 'counting_started_at' => now()]);
        $oldSpecies = FishSpecies::factory()->create();
        $newSpecies = FishSpecies::factory()->create();
        $trip->catchDetails()->create(['species_id' => $oldSpecies->id, 'captain_reported_kg' => 5, 'verified_kg' => 5]);

        $this->actingAs($user)->put(route('dashboard.employee-operations.trips.catch', $trip), ['catches' => [[
            'species_id' => $newSpecies->id, 'reported_kg' => 100, 'verified_kg' => 125, 'boxes_count' => 4,
        ]]])->assertSessionHasNoErrors();

        $this->assertSame('pending_review', $trip->fresh()->status);
        $this->assertDatabaseMissing('catch_details', ['trip_id' => $trip->id, 'species_id' => $oldSpecies->id]);
        $this->assertDatabaseHas('trip_discrepancies', ['trip_id' => $trip->id, 'severity' => 'major', 'review_status' => 'pending']);
    }

    private function statEmployee(): array
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'stat_employee')->value('id')]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        return [$user, $employee];
    }
}
