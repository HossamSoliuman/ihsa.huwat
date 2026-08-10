<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Leave;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmploymentProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unlinked_portal_account_sees_private_setup_state(): void
    {
        $user = $this->portalUser();

        $this->actingAs($user)->get(route('dashboard.profile.show'))
            ->assertOk()->assertSee('ملفك الوظيفي قيد التجهيز');
    }

    public function test_profile_renders_only_authenticated_employees_records(): void
    {
        [$user, $employee] = $this->portalEmployee();
        $otherEmployee = Employee::factory()->create();
        $port = Port::factory()->create(['name' => 'ميناء ملفي']);
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        Attendance::factory()->create(['employee_id' => $employee->id, 'status' => 'late']);
        $run = PayrollRun::factory()->create(['status' => PayrollRun::STATUS_APPROVED, 'run_number' => 'PR-PROFILE']);
        PayrollRunEmployee::factory()->for($run, 'payrollRun')->for($employee)->create(['employee_name' => $user->full_name, 'net_salary' => 8123]);
        PayrollRunEmployee::factory()->for($run, 'payrollRun')->for($otherEmployee)->create(['employee_name' => $otherEmployee->user->full_name, 'net_salary' => 99999]);

        $this->actingAs($user)->get(route('dashboard.profile.show'))
            ->assertOk()->assertSee($user->full_name)->assertSee($port->name)
            ->assertSee('8,123.00')->assertDontSee('99,999.00')->assertDontSee($otherEmployee->user->full_name);
    }

    public function test_employee_can_submit_future_leave_request(): void
    {
        [$user, $employee] = $this->portalEmployee();

        $this->actingAs($user)->post(route('dashboard.profile.leaves.store'), [
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
            'reason' => 'إجازة عائلية',
        ])->assertSessionHasNoErrors();

        $leave = Leave::query()->firstOrFail();
        $this->assertSame($employee->id, $leave->employee_id);
        $this->assertSame('pending', $leave->status);
    }

    public function test_overlapping_pending_leave_is_rejected(): void
    {
        [$user, $employee] = $this->portalEmployee();
        Leave::factory()->create([
            'employee_id' => $employee->id, 'status' => 'pending',
            'start_date' => today()->addDays(3), 'end_date' => today()->addDays(6),
        ]);

        $this->actingAs($user)->post(route('dashboard.profile.leaves.store'), [
            'start_date' => today()->addDays(5)->toDateString(),
            'end_date' => today()->addDays(8)->toDateString(),
        ])->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('leaves', 1);
    }

    public function test_non_portal_role_cannot_open_self_service_profile(): void
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'hr_manager')->value('id')]);

        $this->actingAs($user)->get(route('dashboard.profile.show'))->assertForbidden();
    }

    private function portalUser(): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', 'employee_portal')->value('id')]);
    }

    private function portalEmployee(): array
    {
        $user = $this->portalUser();

        return [$user, Employee::factory()->create(['user_id' => $user->id])];
    }
}
