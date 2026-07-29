<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unrelated_role_cannot_open_payroll(): void
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'quality_supervisor')->value('id')]);

        $this->actingAs($user)->get(route('dashboard.payroll.index'))->assertForbidden();
    }

    public function test_generation_calculates_overtime_absence_and_net_salary(): void
    {
        $user = $this->financeOfficer();
        $employee = Employee::factory()->create(['base_salary' => 7200]);
        $shift = Shift::query()->where('name', 'morning')->firstOrFail();
        Attendance::factory()->create([
            'employee_id' => $employee->id, 'shift_id' => $shift->id,
            'attendance_date' => today(), 'check_in' => today()->setTime(6, 0),
            'check_out' => today()->setTime(16, 0), 'status' => 'present',
        ]);
        Attendance::factory()->create([
            'employee_id' => $employee->id, 'shift_id' => $shift->id,
            'attendance_date' => today()->subDay(), 'status' => 'absent',
        ]);

        $this->actingAs($user)->post(route('dashboard.payroll.generate'), [
            'month' => today()->month, 'year' => today()->year,
        ])->assertRedirect(route('dashboard.payroll.index', ['month' => today()->month, 'year' => today()->year]));

        $payroll = Payroll::query()->firstOrFail();
        $this->assertSame('2.00', $payroll->overtime_hours);
        $this->assertSame('90.00', $payroll->overtime_amount);
        $this->assertSame('240.00', $payroll->deductions);
        $this->assertSame('7050.00', $payroll->net_salary);
    }

    public function test_generation_is_idempotent_for_employee_period(): void
    {
        $user = $this->financeOfficer();
        Employee::factory()->create();
        $payload = ['month' => today()->month, 'year' => today()->year];

        $this->actingAs($user)->post(route('dashboard.payroll.generate'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('dashboard.payroll.generate'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payroll', 1);
    }

    public function test_adjustments_recalculate_net_salary(): void
    {
        $payroll = Payroll::factory()->create(['base_salary' => 7000, 'overtime_amount' => 100]);

        $this->actingAs($this->financeOfficer())->put(route('dashboard.payroll.update', $payroll), [
            'allowances' => 200, 'bonuses' => 50, 'deductions' => 25,
        ])->assertSessionHasNoErrors();

        $this->assertSame('7325.00', $payroll->fresh()->net_salary);
    }

    public function test_payment_finalizes_record_and_prevents_later_changes(): void
    {
        $user = $this->financeOfficer();
        $payroll = Payroll::factory()->create();

        $this->actingAs($user)->patch(route('dashboard.payroll.pay', $payroll))->assertSessionHasNoErrors();
        $this->assertSame('paid', $payroll->fresh()->paid_status);
        $this->assertNotNull($payroll->fresh()->paid_at);

        $this->actingAs($user)->put(route('dashboard.payroll.update', $payroll), [
            'allowances' => 1, 'bonuses' => 1, 'deductions' => 1,
        ])->assertSessionHasErrors('payroll');
        $this->actingAs($user)->patch(route('dashboard.payroll.pay', $payroll))->assertSessionHasErrors('payroll');
    }

    private function financeOfficer(): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', 'finance_officer')->value('id')]);
    }
}
