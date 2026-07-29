<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Port;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HumanResourcesManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_non_hr_role_is_forbidden(): void
    {
        $user = User::factory()->create(['role_id' => Role::where('code', 'quality_supervisor')->value('id')]);
        $this->actingAs($user)->get(route('dashboard.hr.index'))->assertForbidden();
    }

    public function test_hr_can_create_employee_with_hashed_password(): void
    {
        $this->actingAs($this->hr())->post(route('dashboard.hr.employees.store'), ['full_name' => 'موظف جديد', 'username' => 'new_employee', 'password' => 'Strong1234', 'password_confirmation' => 'Strong1234', 'hire_date' => today()->toDateString(), 'contract_type' => 'permanent'])->assertSessionHasNoErrors();
        $user = User::where('username', 'new_employee')->firstOrFail();
        $this->assertTrue(Hash::check('Strong1234', $user->password_hash));
        $this->assertNotNull($user->employee);
    }

    public function test_leave_review_is_atomic_and_cannot_repeat(): void
    {
        $reviewer = $this->hr();
        $leave = Leave::factory()->create(['status' => 'pending', 'start_date' => today(), 'end_date' => today()->addDay()]);
        $this->actingAs($reviewer)->patch(route('dashboard.hr.leaves.review', $leave), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($reviewer->id, $leave->fresh()->approved_by);
        $this->assertSame('on_leave', $leave->employee->fresh()->status);
        $this->actingAs($reviewer)->patch(route('dashboard.hr.leaves.review', $leave), ['decision' => 'rejected'])->assertSessionHasErrors('leave');
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_assignment_upserts_employee_day(): void
    {
        $employee = Employee::factory()->create();
        $first = Port::factory()->create();
        $second = Port::factory()->create();
        $shift = Shift::firstOrFail();
        $payload = ['employee_id' => $employee->id, 'port_id' => $first->id, 'shift_id' => $shift->id, 'assignment_date' => today()->toDateString()];
        $this->actingAs($this->hr())->post(route('dashboard.hr.assignments.store'), $payload)->assertSessionHasNoErrors();
        $payload['port_id'] = $second->id;
        $this->actingAs($this->hr())->post(route('dashboard.hr.assignments.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('employee_assignments', 1);
        $this->assertDatabaseHas('employee_assignments', ['employee_id' => $employee->id, 'port_id' => $second->id]);
    }

    public function test_dashboard_renders_pending_leave_and_expiring_contract(): void
    {
        $employee = Employee::factory()->create(['contract_type' => 'temporary', 'contract_end_date' => today()->addDays(10)]);
        $leave = Leave::factory()->create(['employee_id' => $employee->id, 'status' => 'pending']);
        $this->actingAs($this->hr())->get(route('dashboard.hr.index'))->assertOk()->assertSee($employee->user->full_name)->assertSee($leave->reason);
    }

    private function hr(): User
    {
        return User::factory()->create(['role_id' => Role::where('code', 'hr_manager')->value('id')]);
    }
}
