<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Role;
use App\Models\Trip;
use App\Models\TripAttachment;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeePerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unrelated_role_cannot_open_employee_performance(): void
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'finance_officer')->value('id')]);

        $this->actingAs($user)->get(route('dashboard.employee-performance.index'))->assertForbidden();
    }

    public function test_dashboard_calculates_employee_throughput_quality_and_documentation(): void
    {
        $user = $this->superAdmin();
        $employee = Employee::factory()->create();
        $port = Port::factory()->create(['name' => 'ميناء الأداء']);
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $trip = Trip::factory()->create([
            'assigned_employee_id' => $employee->id, 'port_id' => $port->id, 'status' => 'approved',
            'actual_arrival' => now(), 'verified_weight' => 250,
            'counting_started_at' => now()->subMinutes(40), 'counting_ended_at' => now(),
        ]);
        TripAttachment::factory()->create(['trip_id' => $trip->id]);
        TripDiscrepancy::factory()->create(['trip_id' => $trip->id, 'diff_percent' => 2]);

        $this->actingAs($user)->get(route('dashboard.employee-performance.index'))
            ->assertOk()->assertSee($employee->user->full_name)->assertSee($port->name)
            ->assertSee('250')->assertSee('40')->assertSee('100%')->assertSee('ممتاز');
    }

    public function test_governorate_supervisor_only_sees_performance_from_their_governorate(): void
    {
        $governorate = Governorate::factory()->create();
        $otherGovernorate = Governorate::factory()->create();
        $port = Port::factory()->create(['governorate_id' => $governorate->id]);
        $otherPort = Port::factory()->create(['governorate_id' => $otherGovernorate->id]);
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'gov_supervisor')->value('id'), 'governorate_id' => $governorate->id]);
        $visible = Employee::factory()->create();
        $hidden = Employee::factory()->create();
        Trip::factory()->create(['assigned_employee_id' => $visible->id, 'port_id' => $port->id, 'status' => 'approved', 'actual_arrival' => now()]);
        Trip::factory()->create(['assigned_employee_id' => $hidden->id, 'port_id' => $otherPort->id, 'status' => 'approved', 'actual_arrival' => now()]);

        $this->actingAs($user)->get(route('dashboard.employee-performance.index'))
            ->assertOk()->assertSee($visible->user->full_name)->assertDontSee($hidden->user->full_name);
    }

    public function test_date_range_must_be_chronological(): void
    {
        $this->actingAs($this->superAdmin())->get(route('dashboard.employee-performance.index', [
            'date_from' => '2026-07-20', 'date_to' => '2026-07-01',
        ]))->assertSessionHasErrors('date_to');
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', 'super_admin')->value('id')]);
    }
}
