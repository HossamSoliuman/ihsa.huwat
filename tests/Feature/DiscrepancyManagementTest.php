<?php

namespace Tests\Feature;

use App\Models\CatchDetail;
use App\Models\Port;
use App\Models\Role;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DiscrepancyManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_quality_roles_can_open_discrepancy_dashboard(): void
    {
        $role = Role::query()->where('code', 'hr_manager')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('dashboard.discrepancies.index'))->assertForbidden();
    }

    public function test_dashboard_renders_analytics_and_pending_queue(): void
    {
        $user = $this->qualitySupervisor();
        $trip = Trip::factory()->create(['status' => 'pending_review', 'actual_arrival' => now()]);
        $discrepancy = TripDiscrepancy::factory()->create(['trip_id' => $trip->id, 'severity' => 'major', 'diff_percent' => 15]);
        CatchDetail::factory()->create(['trip_id' => $trip->id, 'is_unreported_by_captain' => true]);

        $this->actingAs($user)
            ->get(route('dashboard.discrepancies.index'))
            ->assertOk()
            ->assertSee($trip->trip_code)
            ->assertSee($discrepancy->reason)
            ->assertSee('الفروقات المعلقة');
    }

    public function test_approval_atomically_updates_discrepancy_and_trip(): void
    {
        $user = $this->qualitySupervisor();
        $trip = Trip::factory()->create(['status' => 'pending_review', 'actual_arrival' => now()]);
        $discrepancy = TripDiscrepancy::factory()->create(['trip_id' => $trip->id, 'review_status' => 'pending']);

        $this->actingAs($user)
            ->patch(route('dashboard.discrepancies.approve', $discrepancy))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $discrepancy->fresh()->review_status);
        $this->assertSame($user->id, $discrepancy->fresh()->reviewed_by);
        $this->assertNotNull($discrepancy->fresh()->reviewed_at);
        $this->assertSame('approved', $trip->fresh()->status);
        $this->assertSame($user->id, $trip->fresh()->approved_by);
    }

    public function test_port_supervisor_cannot_approve_another_ports_discrepancy(): void
    {
        $assignedPort = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $role = Role::query()->where('code', 'port_supervisor')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'port_id' => $assignedPort->id]);
        $trip = Trip::factory()->create(['port_id' => $otherPort->id, 'status' => 'pending_review', 'actual_arrival' => now()]);
        $discrepancy = TripDiscrepancy::factory()->create(['trip_id' => $trip->id]);

        $this->actingAs($user)->patch(route('dashboard.discrepancies.approve', $discrepancy))->assertForbidden();
        $this->assertSame('pending', $discrepancy->fresh()->review_status);
    }

    public function test_approval_rejects_trip_outside_review_stage(): void
    {
        $user = $this->qualitySupervisor();
        $trip = Trip::factory()->create(['status' => 'closed', 'actual_arrival' => now()]);
        $discrepancy = TripDiscrepancy::factory()->create(['trip_id' => $trip->id]);

        $this->actingAs($user)
            ->from(route('dashboard.discrepancies.index'))
            ->patch(route('dashboard.discrepancies.approve', $discrepancy))
            ->assertSessionHasErrors('trip');
        $this->assertSame('closed', $trip->fresh()->status);
    }

    private function qualitySupervisor(): User
    {
        $role = Role::query()->where('code', 'quality_supervisor')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
