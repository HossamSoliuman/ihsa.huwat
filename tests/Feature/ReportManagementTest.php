<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CatchDetail;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Governorate;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_every_report_family_renders_with_native_laravel_data_queries(): void
    {
        [$user, $trip] = $this->reportFixture();

        foreach (array_keys(config('reports.types')) as $type) {
            $this->actingAs($user)->get(route('dashboard.reports.index', [
                'report_type' => $type,
                'date_from' => today()->subMonth()->toDateString(),
                'date_to' => today()->addMonth()->toDateString(),
            ]))->assertOk()->assertSee(config("reports.types.{$type}"));
        }

        $this->actingAs($user)->get(route('dashboard.reports.index'))->assertSee($trip->trip_code);
    }

    public function test_region_manager_cannot_see_trip_or_payroll_data_from_another_region(): void
    {
        $region = Region::factory()->create();
        $port = Port::factory()->create(['governorate_id' => Governorate::factory()->create(['region_id' => $region->id])]);
        $otherPort = Port::factory()->create();
        $visibleTrip = Trip::factory()->create(['port_id' => $port->id, 'trip_code' => 'VISIBLE-TRIP', 'actual_arrival' => now()]);
        Trip::factory()->create(['port_id' => $otherPort->id, 'trip_code' => 'HIDDEN-TRIP', 'actual_arrival' => now()]);
        $visibleEmployee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $visibleEmployee->id, 'port_id' => $port->id]);
        $hiddenEmployee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $hiddenEmployee->id, 'port_id' => $otherPort->id]);
        Payroll::factory()->create(['employee_id' => $visibleEmployee->id, 'period_month' => today()->month, 'period_year' => today()->year]);
        Payroll::factory()->create(['employee_id' => $hiddenEmployee->id, 'period_month' => today()->month, 'period_year' => today()->year]);
        $user = $this->user('region_manager', ['region_id' => $region->id]);

        $this->actingAs($user)->get(route('dashboard.reports.index'))->assertSee($visibleTrip->trip_code)->assertDontSee('HIDDEN-TRIP');
        $this->actingAs($user)->get(route('dashboard.reports.index', ['report_type' => 'payroll']))
            ->assertSee($visibleEmployee->user->full_name)->assertDontSee($hiddenEmployee->user->full_name);
    }

    public function test_governorate_supervisor_cannot_filter_to_an_out_of_scope_port(): void
    {
        $governorate = Governorate::factory()->create();
        $otherPort = Port::factory()->create();
        $user = $this->user('gov_supervisor', ['governorate_id' => $governorate->id]);

        $this->actingAs($user)->get(route('dashboard.reports.index', ['port_id' => $otherPort->id]))
            ->assertSessionHasErrors('port_id');
    }

    public function test_current_report_can_be_exported_as_utf8_csv(): void
    {
        [$user, $trip] = $this->reportFixture();

        $response = $this->actingAs($user)->get(route('dashboard.reports.export', [
            'report_type' => 'trips',
            'date_from' => today()->subDay()->toDateString(),
            'date_to' => today()->addDay()->toDateString(),
        ]));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($trip->trip_code, $response->streamedContent());
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
    }

    private function reportFixture(): array
    {
        $port = Port::factory()->create();
        $employee = Employee::factory()->create();
        $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id, 'assignment_date' => today()]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'shift_id' => $assignment->shift_id, 'attendance_date' => today()]);
        Leave::factory()->create(['employee_id' => $employee->id, 'start_date' => today(), 'end_date' => today()->addDay()]);
        Payroll::factory()->create(['employee_id' => $employee->id, 'period_month' => today()->month, 'period_year' => today()->year]);
        $trip = Trip::factory()->create([
            'port_id' => $port->id, 'assigned_employee_id' => $employee->id, 'status' => 'approved',
            'actual_arrival' => now(), 'approved_at' => now(), 'verified_weight' => 250,
        ]);
        TripDiscrepancy::factory()->create(['trip_id' => $trip->id]);
        CatchDetail::factory()->create(['trip_id' => $trip->id]);

        return [$this->user('super_admin'), $trip];
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role_id' => Role::query()->where('code', $role)->value('id')]);
    }
}
