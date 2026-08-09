<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\JobTitle;
use App\Models\Nationality;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeDirectoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_can_create_employee_account_and_first_contract(): void
    {
        $department = Department::factory()->create();
        $jobTitle = JobTitle::factory()->create();
        $port = Port::factory()->create();
        $employeeRole = Role::query()->where('code', 'employee_portal')->firstOrFail();

        $response = $this->actingAs($this->userWithRole('hr_manager'))
            ->post(route('dashboard.hr.employees.store'), $this->employeePayload([
                'department_id' => $department->id,
                'job_title_id' => $jobTitle->id,
                'port_id' => $port->id,
                'role_id' => $employeeRole->id,
            ]));

        $response->assertSessionHasNoErrors();
        $employee = Employee::query()->where('email', 'employee@example.test')->firstOrFail();
        $response->assertRedirect(route('dashboard.hr.employees.show', $employee));
        $this->assertMatchesRegularExpression('/^HWT-\d{5}$/', $employee->employee_number);
        $this->assertTrue(Hash::check('StrongPass123', $employee->user->password_hash));
        $this->assertSame('active', $employee->activeContract()->firstOrFail()->status);
        $this->assertSame('fixed_term', $employee->activeContract()->firstOrFail()->contract_type);
        $this->assertDatabaseHas('employee_salary_components', [
            'employee_id' => $employee->id,
            'amount' => 9000,
        ]);
    }

    public function test_directory_filters_by_contract_and_search_without_exposing_salary(): void
    {
        $matchingUser = User::factory()->create(['full_name' => 'موظف العقود']);
        $matching = Employee::factory()->for($matchingUser)->create();
        EmployeeContract::factory()->for($matching)->temporary()->create();
        $other = Employee::factory()->create();
        EmployeeContract::factory()->for($other)->create();

        $response = $this->actingAs($this->userWithRole('finance_officer'))
            ->get(route('dashboard.hr.employees.index', ['search' => 'موظف العقود', 'contract_type' => 'temporary']));

        $response->assertOk()
            ->assertSee('موظف العقود')
            ->assertDontSee($other->user->full_name)
            ->assertDontSee('12,345.67');
    }

    public function test_csv_export_contains_filtered_employee_without_salary(): void
    {
        $user = User::factory()->create(['full_name' => 'موظف التصدير']);
        $employee = Employee::factory()->for($user)->create();
        EmployeeContract::factory()->for($employee)->create();

        $response = $this->actingAs($this->userWithRole('hr_manager'))
            ->get(route('dashboard.hr.employees.export', ['search' => 'موظف التصدير']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('موظف التصدير', $content);
        $this->assertStringNotContainsString('87654.32', $content);
    }

    private function employeePayload(array $overrides = []): array
    {
        return [
            'full_name' => 'موظف جديد',
            'username' => 'new.employee',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'role_id' => Role::query()->where('code', 'employee_portal')->valueOrFail('id'),
            'national_id' => '1099999999',
            'nationality' => array_key_first(Nationality::options()),
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '0500000000',
            'email' => 'employee@example.test',
            'department_id' => Department::factory()->create()->id,
            'job_title_id' => JobTitle::factory()->create()->id,
            'manager_id' => null,
            'port_id' => null,
            'hire_date' => '2026-01-01',
            'contract_type' => 'fixed_term',
            'contract_start_date' => '2026-01-01',
            'contract_end_date' => '2026-12-31',
            'probation_end_date' => '2026-03-31',
            'working_hours_per_day' => 8,
            'working_days_per_week' => 6,
            'base_salary' => 9000,
            ...$overrides,
        ];
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
