<?php

namespace Tests\Feature;

use App\Actions\ApproveEmployeeLoanAction;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollRun;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PayrollFinancialRecordsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_salary_change_closes_previous_row_and_preserves_audit_history(): void
    {
        $hrManager = $this->userWithRole('hr_manager');
        $employee = Employee::factory()->create();
        $basic = SalaryComponent::query()->where('code', 'basic')->firstOrFail();
        $previous = EmployeeSalaryComponent::factory()->for($employee)->create([
            'salary_component_id' => $basic->id,
            'amount' => 7000,
            'effective_from' => today()->startOfYear(),
            'created_by' => $hrManager->id,
        ]);

        $this->actingAs($hrManager)->post(route('dashboard.hr.employees.salary.store', [$employee, $basic]), [
            'amount' => 8500,
            'effective_from' => today()->toDateString(),
            'reason' => 'ترقية سنوية',
        ])->assertSessionHasNoErrors();

        $this->assertSame(today()->subDay()->toDateString(), $previous->fresh()->effective_to->toDateString());
        $this->assertDatabaseHas('employee_salary_components', ['employee_id' => $employee->id, 'amount' => 8500]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'salary_component_changed', 'reason' => 'ترقية سنوية']);
    }

    public function test_finance_approval_builds_exact_loan_schedule(): void
    {
        $employee = Employee::factory()->create();
        $hrManager = $this->userWithRole('hr_manager');
        $financeOfficer = $this->userWithRole('finance_officer');

        $this->actingAs($hrManager)->post(route('dashboard.hr.employees.loans.store', $employee), [
            'amount' => 1000,
            'instalments_count' => 3,
            'first_instalment_month' => today()->addMonth()->format('Y-m'),
            'reason' => 'سلفة طارئة',
        ])->assertSessionHasNoErrors();
        $loan = $employee->loans()->firstOrFail();

        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.loans.approve', $loan))->assertSessionHasNoErrors();

        $this->assertSame('approved', $loan->fresh()->status);
        $this->assertSame(3, $loan->instalments()->count());
        $this->assertSame('1000.00', number_format((float) $loan->instalments()->sum('amount'), 2, '.', ''));
    }

    public function test_every_instalment_count_from_one_to_twenty_four_sums_exactly_to_the_loan(): void
    {
        $employee = Employee::factory()->create();
        $financeOfficer = $this->userWithRole('finance_officer');
        $action = app(ApproveEmployeeLoanAction::class);

        foreach (range(1, 24) as $count) {
            $loan = $employee->loans()->create([
                'loan_number' => 'LN-EXACT-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT),
                'amount' => 1000.01,
                'instalments_count' => $count,
                'instalment_amount' => round(1000.01 / $count, 2),
                'first_instalment_month' => today()->addMonth()->startOfMonth(),
                'reason' => 'اختبار دقة الأقساط',
                'status' => 'requested',
            ]);

            $action->execute($loan, $financeOfficer);
            $sumInCents = $loan->instalments()->get()->sum(fn ($instalment): int => (int) round((float) $instalment->amount * 100));
            $this->assertSame(100001, $sumInCents, 'فشل عدد الأقساط '.$count);
        }
    }

    public function test_adjustment_cannot_be_added_to_an_approved_period(): void
    {
        $employee = Employee::factory()->create();
        $financeOfficer = $this->userWithRole('finance_officer');
        PayrollRun::factory()->create([
            'status' => PayrollRun::STATUS_APPROVED,
            'period_year' => today()->year,
            'period_month' => today()->month,
            'run_number' => 'PR-LOCKED',
        ]);

        $this->actingAs($financeOfficer)->post(route('dashboard.hr.employees.adjustments.store', $employee), [
            'adjustment_type' => 'earning',
            'period_year' => today()->year,
            'period_month' => today()->month,
            'amount' => 100,
            'reason' => 'مكافأة',
        ])->assertSessionHasErrors('period');
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
