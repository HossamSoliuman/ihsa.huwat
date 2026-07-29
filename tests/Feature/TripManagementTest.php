<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\Port;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TripManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthorized_roles_cannot_view_trips(): void
    {
        $role = Role::query()->where('code', 'hr_manager')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('dashboard.trips.index'))->assertForbidden();
    }

    public function test_port_supervisor_only_sees_trips_for_assigned_port(): void
    {
        $role = Role::query()->where('code', 'port_supervisor')->firstOrFail();
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id, 'port_id' => $port->id]);
        $visible = Trip::factory()->create(['port_id' => $port->id, 'trip_code' => 'VISIBLE-TRIP']);
        Trip::factory()->create(['port_id' => $otherPort->id, 'trip_code' => 'HIDDEN-TRIP']);

        $this->actingAs($user)
            ->get(route('dashboard.trips.index', ['date_from' => today()->format('Y-m-d'), 'date_to' => today()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee($visible->trip_code)
            ->assertDontSee('HIDDEN-TRIP');
    }

    public function test_super_administrator_can_create_and_mark_trip_arrived(): void
    {
        $administrator = $this->administrator();
        $port = Port::factory()->create();
        $boat = Boat::factory()->create(['home_port_id' => $port->id]);
        $captain = Captain::factory()->create();

        $this->actingAs($administrator)->post(route('dashboard.trips.store'), [
            'trip_code' => 'TRIP-2026-001',
            'boat_id' => $boat->id,
            'captain_id' => $captain->id,
            'port_id' => $port->id,
            'expected_arrival' => now()->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('dashboard.trips.index'));

        $trip = Trip::query()->where('trip_code', 'TRIP-2026-001')->firstOrFail();
        $this->actingAs($administrator)->patch(route('dashboard.trips.arrive', $trip))->assertSessionHasNoErrors();

        $this->assertSame('arrived', $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->actual_arrival);
    }

    public function test_invalid_arrival_transition_is_rejected(): void
    {
        $administrator = $this->administrator();
        $trip = Trip::factory()->create(['status' => 'approved']);

        $this->actingAs($administrator)
            ->from(route('dashboard.trips.index'))
            ->patch(route('dashboard.trips.arrive', $trip))
            ->assertSessionHasErrors('trip');
    }

    private function administrator(): User
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
