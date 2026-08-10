<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EmployeeContractTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_hr_can_renew_an_active_contract(): void
    {
        $employee = Employee::factory()->create();
        $currentContract = EmployeeContract::factory()->for($employee)->create([
            'start_date' => '2025-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($this->userWithRole('hr_manager'))
            ->post(route('dashboard.hr.employees.contracts.renew', $employee), $this->contractPayload([
                'start_date' => '2027-01-01',
                'end_date' => '2027-12-31',
                'probation_end_date' => '2027-03-31',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('expired', $currentContract->fresh()->status);
        $this->assertSame('2026-12-31', $currentContract->fresh()->end_date->toDateString());
        $this->assertSame(1, $employee->contracts()->where('status', 'active')->count());
        $this->assertMatchesRegularExpression('/^HWT-C-\d{5}$/', $employee->activeContract()->firstOrFail()->contract_number);
    }

    public function test_employee_cannot_receive_a_second_active_contract(): void
    {
        $employee = Employee::factory()->create();
        EmployeeContract::factory()->for($employee)->create();

        $this->actingAs($this->userWithRole('hr_manager'))
            ->post(route('dashboard.hr.employees.contracts.store', $employee), $this->contractPayload())
            ->assertSessionHasErrors('contract');

        $this->assertSame(1, $employee->contracts()->where('status', 'active')->count());
    }

    public function test_finance_officer_cannot_mutate_contracts(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->userWithRole('finance_officer'))
            ->post(route('dashboard.hr.employees.contracts.store', $employee), $this->contractPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('employee_contracts', ['employee_id' => $employee->id]);
    }

    private function contractPayload(array $overrides = []): array
    {
        return [
            'contract_type' => 'fixed_term',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'probation_end_date' => '2026-03-31',
            'working_hours_per_day' => 8,
            'working_days_per_week' => 6,
            'note' => null,
            ...$overrides,
        ];
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
