<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryComponent;
use App\Models\LoanInstalment;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unrelated_role_cannot_open_payroll(): void
    {
        $user = $this->userWithRole('quality_supervisor');

        $this->actingAs($user)->get(route('dashboard.payroll.index'))->assertForbidden();
    }

    public function test_finance_officer_can_open_payroll_and_financial_input_registers(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');

        $this->actingAs($financeOfficer)->get(route('dashboard.payroll.index'))->assertOk()->assertSee('مسيرات الرواتب');
        $this->actingAs($financeOfficer)->get(route('dashboard.hr.adjustments.index'))->assertOk()->assertSee('الاستحقاقات والخصومات');
        $this->actingAs($financeOfficer)->get(route('dashboard.hr.loans.index'))->assertOk()->assertSee('السلف والأقساط');
    }

    public function test_calculation_snapshots_salary_components_adjustments_and_loan_instalments(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $employee = $this->payrollReadyEmployee(7200);
        $this->salary($employee, 'housing', percentage: 25);
        $this->salary($employee, 'transport', amount: 500);
        $adjustment = PayrollAdjustment::factory()->for($employee)->create([
            'adjustment_type' => 'earning',
            'period_year' => today()->year,
            'period_month' => today()->month,
            'amount' => 300,
            'status' => PayrollAdjustment::STATUS_APPROVED,
            'approved_by' => $financeOfficer->id,
        ]);
        $loan = EmployeeLoan::factory()->for($employee)->create(['status' => 'approved']);
        $instalment = LoanInstalment::factory()->for($loan, 'loan')->create([
            'due_year' => today()->year,
            'due_month' => today()->month,
            'amount' => 400,
            'status' => 'scheduled',
        ]);

        $run = $this->createAndCalculateRun($financeOfficer);
        $snapshot = PayrollRunEmployee::query()->where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame('9800.00', $snapshot->total_earnings);
        $this->assertSame('400.00', $snapshot->total_deductions);
        $this->assertSame('9400.00', $snapshot->net_salary);
        $this->assertDatabaseHas('payroll_run_items', ['payroll_run_employee_id' => $snapshot->id, 'source_type' => PayrollAdjustment::class, 'source_id' => $adjustment->id]);
        $this->assertDatabaseHas('payroll_run_items', ['payroll_run_employee_id' => $snapshot->id, 'source_type' => LoanInstalment::class, 'source_id' => $instalment->id]);
        $this->actingAs($financeOfficer)->get(route('dashboard.payroll.runs.show', $run))->assertOk()->assertSee('مبلغ ثابت');
        $this->actingAs($financeOfficer)->get(route('dashboard.payroll.runs.employees.show', [$run, $snapshot]))->assertOk()->assertSee('طريقة الاحتساب')->assertSee('مبلغ ثابت');
    }

    public function test_approval_consumes_financial_inputs_and_lifecycle_seals_the_run(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $employee = $this->payrollReadyEmployee(7000);
        $adjustment = PayrollAdjustment::factory()->for($employee)->create([
            'period_year' => today()->year,
            'period_month' => today()->month,
            'status' => PayrollAdjustment::STATUS_APPROVED,
            'approved_by' => $financeOfficer->id,
        ]);
        $run = $this->createAndCalculateRun($financeOfficer);

        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.approve', $run))->assertSessionHasNoErrors();
        $this->assertSame(PayrollRun::STATUS_APPROVED, $run->fresh()->status);
        $this->assertSame(PayrollAdjustment::STATUS_CONSUMED, $adjustment->fresh()->status);

        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.paid', $run), [
            'payment_date' => today()->toDateString(),
            'payment_reference' => 'BANK-2026-08',
        ])->assertSessionHasNoErrors();
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.close', $run))->assertSessionHasNoErrors();

        $this->assertSame(PayrollRun::STATUS_CLOSED, $run->fresh()->status);
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.calculate', $run))->assertSessionHasErrors('run');
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll_run_closed', 'model_id' => $run->id]);
    }

    public function test_period_can_only_have_one_run(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $payload = ['period_year' => today()->year, 'period_month' => today()->month];

        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.store'), $payload)->assertSessionHasErrors('period_month');

        $this->assertSame(1, PayrollRun::query()->where('period_year', today()->year)->where('period_month', today()->month)->count());
    }

    public function test_employee_can_open_own_payslip_but_not_another_employee_payslip(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $employee = $this->payrollReadyEmployee(6500);
        $otherEmployee = $this->payrollReadyEmployee(8100);
        $run = $this->createAndCalculateRun($financeOfficer);
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.approve', $run))->assertSessionHasNoErrors();
        $ownSnapshot = $run->employees()->where('employee_id', $employee->id)->firstOrFail();
        $otherSnapshot = $run->employees()->where('employee_id', $otherEmployee->id)->firstOrFail();

        $this->actingAs($employee->user)->get(route('dashboard.payslips.show', $ownSnapshot))->assertOk()->assertSee($employee->user->full_name);
        $this->actingAs($employee->user)->get(route('dashboard.payslips.show', $otherSnapshot))->assertForbidden();
        $this->actingAs($this->userWithRole('port_supervisor'))->get(route('dashboard.payslips.show', $ownSnapshot))->assertForbidden();
    }

    public function test_recalculation_is_idempotent(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $employee = $this->payrollReadyEmployee(7300);
        $this->salary($employee, 'housing', percentage: 20);
        $run = $this->createAndCalculateRun($financeOfficer);
        $first = $run->employees()->where('employee_id', $employee->id)->with('items')->firstOrFail();
        $firstTotals = $first->only(['basic_salary', 'total_earnings', 'total_deductions', 'net_salary']);
        $firstItems = $first->items->map->only(['code', 'amount', 'calculation_details'])->values()->all();

        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.calculate', $run))->assertSessionHasNoErrors();
        $second = $run->employees()->where('employee_id', $employee->id)->with('items')->firstOrFail();

        $this->assertSame($firstTotals, $second->only(['basic_salary', 'total_earnings', 'total_deductions', 'net_salary']));
        $this->assertSame($firstItems, $second->items->map->only(['code', 'amount', 'calculation_details'])->values()->all());
    }

    public function test_future_salary_change_does_not_change_an_approved_payslip(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $hrManager = $this->userWithRole('hr_manager');
        $employee = $this->payrollReadyEmployee(7000);
        $run = $this->createAndCalculateRun($financeOfficer);
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.approve', $run))->assertSessionHasNoErrors();
        $snapshot = $run->employees()->where('employee_id', $employee->id)->firstOrFail();
        $basic = SalaryComponent::query()->where('code', 'basic')->firstOrFail();

        $this->actingAs($hrManager)->post(route('dashboard.hr.employees.salary.store', [$employee, $basic]), [
            'amount' => 9000,
            'effective_from' => today()->addMonth()->startOfMonth()->toDateString(),
            'reason' => 'زيادة مستقبلية',
        ])->assertSessionHasNoErrors();

        $this->assertSame('7000.00', $snapshot->fresh()->basic_salary);
        $this->actingAs($employee->user)->get(route('dashboard.payslips.show', $snapshot))->assertOk()->assertSee('7,000.00');
    }

    public function test_negative_net_is_clamped_and_reported_as_an_error(): void
    {
        $financeOfficer = $this->userWithRole('finance_officer');
        $employee = $this->payrollReadyEmployee(500);
        PayrollAdjustment::factory()->for($employee)->create([
            'adjustment_type' => 'deduction',
            'period_year' => today()->year,
            'period_month' => today()->month,
            'amount' => 1000,
            'status' => PayrollAdjustment::STATUS_APPROVED,
            'approved_by' => $financeOfficer->id,
        ]);

        $run = $this->createAndCalculateRun($financeOfficer);
        $snapshot = $run->employees()->where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame('0.00', $snapshot->net_salary);
        $this->assertDatabaseHas('payroll_run_issues', ['payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'code' => 'negative_net', 'level' => 'error']);
    }

    private function createAndCalculateRun(User $financeOfficer): PayrollRun
    {
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.store'), [
            'period_year' => today()->year,
            'period_month' => today()->month,
        ])->assertSessionHasNoErrors();
        $run = PayrollRun::query()->where('period_year', today()->year)->where('period_month', today()->month)->firstOrFail();
        $this->actingAs($financeOfficer)->post(route('dashboard.payroll.runs.calculate', $run))->assertSessionHasNoErrors();

        return $run->fresh();
    }

    private function payrollReadyEmployee(float $basicSalary): Employee
    {
        $employee = Employee::factory()->for($this->userWithRole('employee_portal'))->create();
        EmployeeContract::factory()->for($employee)->create([
            'start_date' => today()->startOfYear(),
            'end_date' => null,
            'status' => 'active',
        ]);
        $this->salary($employee, 'basic', amount: $basicSalary);

        return $employee;
    }

    private function salary(Employee $employee, string $code, ?float $amount = null, ?float $percentage = null): EmployeeSalaryComponent
    {
        return EmployeeSalaryComponent::factory()->for($employee)->create([
            'salary_component_id' => SalaryComponent::query()->where('code', $code)->valueOrFail('id'),
            'amount' => $amount,
            'percentage' => $percentage,
            'effective_from' => today()->startOfYear(),
            'created_by' => $this->userWithRole('hr_manager')->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', $role)->valueOrFail('id')]);
    }
}
