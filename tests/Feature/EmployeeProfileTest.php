<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeSalaryComponent;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_can_view_employee_identity_contract_and_salary(): void
    {
        $employee = Employee::factory()->create();
        EmployeeSalaryComponent::factory()->for($employee)->create([
            'salary_component_id' => SalaryComponent::query()->where('code', 'basic')->valueOrFail('id'),
            'amount' => 8123.45,
        ]);
        $contract = EmployeeContract::factory()->for($employee)->create();

        $this->actingAs($this->userWithRole('hr_manager'))
            ->get(route('dashboard.hr.employees.show', $employee))
            ->assertOk()
            ->assertSee($employee->user->full_name)
            ->assertSee($contract->contract_number)
            ->assertSee('8,123.45');
    }

    public function test_finance_officer_has_read_only_employee_profile_access(): void
    {
        $employee = Employee::factory()->create();
        EmployeeContract::factory()->for($employee)->create();

        $this->actingAs($this->userWithRole('finance_officer'))
            ->get(route('dashboard.hr.employees.show', $employee))
            ->assertOk()
            ->assertDontSee('تجديد العقد')
            ->assertDontSee('تحديث بيانات الموظف');
    }

    public function test_hr_updates_and_terminates_employee_with_an_audit_record(): void
    {
        $employee = Employee::factory()->create();
        $hrManager = $this->userWithRole('hr_manager');

        $this->actingAs($hrManager)->patch(route('dashboard.hr.employees.update', $employee), [
            'full_name' => 'موظف محدّث',
            'national_id' => $employee->national_id,
            'nationality' => $employee->nationality,
            'date_of_birth' => $employee->date_of_birth->toDateString(),
            'gender' => $employee->gender,
            'phone' => '0500000011',
            'email' => 'updated.employee@example.test',
            'department_id' => $employee->department_id,
            'job_title_id' => $employee->job_title_id,
            'manager_id' => null,
            'port_id' => $employee->port_id,
            'status' => 'terminated',
            'termination_date' => today()->toDateString(),
            'termination_reason' => 'نهاية العلاقة التعاقدية',
        ])->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('terminated', $employee->status);
        $this->assertSame('موظف محدّث', $employee->user->full_name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee_terminated', 'model_id' => $employee->id]);
    }

    public function test_port_supervisor_receives_forbidden_for_employee_profile(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->userWithRole('port_supervisor'))
            ->get(route('dashboard.hr.employees.show', $employee))
            ->assertForbidden()
            ->assertDontSee('99,123.45');
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
