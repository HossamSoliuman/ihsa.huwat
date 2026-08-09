<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\LoanInstalment;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculatePayrollRunAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollRun $run, User $actor, ?string $ipAddress = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $ipAddress): PayrollRun {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if (! in_array($run->status, [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_CALCULATED], true)) {
                throw ValidationException::withMessages(['run' => 'لا يمكن إعادة احتساب المسير في حالته الحالية.']);
            }

            $run->employees()->delete();
            $run->issues()->delete();

            $periodStart = CarbonImmutable::parse($run->period_start);
            $periodEnd = CarbonImmutable::parse($run->period_end);
            $employees = $this->employeesForPeriod($periodStart, $periodEnd, $run);

            foreach ($employees as $employee) {
                $this->calculateEmployee($run, $employee, $periodStart, $periodEnd);
            }

            $totals = $run->employees()->toBase()->selectRaw(
                'COUNT(*) AS employees_count, COALESCE(SUM(total_earnings), 0) AS total_earnings, COALESCE(SUM(total_deductions), 0) AS total_deductions, COALESCE(SUM(net_salary), 0) AS net_total'
            )->first();
            $oldStatus = $run->status;
            $run->forceFill([
                'employees_count' => (int) $totals->employees_count,
                'total_earnings' => round((float) $totals->total_earnings, 2),
                'total_deductions' => round((float) $totals->total_deductions, 2),
                'net_total' => round((float) $totals->net_total, 2),
                'status' => PayrollRun::STATUS_CALCULATED,
                'calculated_at' => now(),
            ])->save();

            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_calculated',
                $run,
                ['status' => $oldStatus],
                $run->only(['status', 'employees_count', 'total_earnings', 'total_deductions', 'net_total']),
                ipAddress: $ipAddress,
            );

            return $run->load(['employees.items', 'issues']);
        });
    }

    /** @return Collection<int, Employee> */
    private function employeesForPeriod(CarbonImmutable $periodStart, CarbonImmutable $periodEnd, PayrollRun $run): Collection
    {
        return Employee::query()
            ->with([
                'user:id,full_name',
                'department:id,name',
                'jobTitle:id,name',
                'port:id,name',
                'contracts' => fn ($query) => $query->whereDate('start_date', '<=', $periodEnd)
                    ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $periodStart))
                    ->whereIn('status', ['active', 'expired'])
                    ->orderByDesc('start_date')->orderByDesc('id'),
                'salaryComponents' => fn ($query) => $query->with('salaryComponent')
                    ->whereDate('effective_from', '<=', $periodEnd)
                    ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodStart))
                    ->orderBy('effective_from')->orderBy('id'),
                'attendance' => fn ($query) => $query->whereBetween('attendance_date', [$periodStart, $periodEnd]),
                'payrollAdjustments' => fn ($query) => $query->where('period_year', $run->period_year)
                    ->where('period_month', $run->period_month)
                    ->where('status', PayrollAdjustment::STATUS_APPROVED)
                    ->orderBy('id'),
                'loans' => fn ($query) => $query->whereIn('status', ['approved', 'active'])
                    ->with(['instalments' => fn ($query) => $query->where('due_year', $run->period_year)
                        ->where('due_month', $run->period_month)
                        ->where('status', 'scheduled')->orderBy('instalment_number')]),
            ])
            ->whereDate('hire_date', '<=', $periodEnd)
            ->whereIn('status', ['active', 'on_leave', 'suspended', 'terminated'])
            ->where(fn (Builder $query) => $query->whereNull('termination_date')->orWhereDate('termination_date', '>=', $periodStart))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function calculateEmployee(
        PayrollRun $run,
        Employee $employee,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): void {
        $contract = $employee->contracts->first();
        $salaryRows = $employee->salaryComponents
            ->groupBy('salary_component_id')
            ->map(fn (Collection $rows): EmployeeSalaryComponent => $rows->sortByDesc('effective_from')->first());
        $basicSalaryRow = $salaryRows->first(fn (EmployeeSalaryComponent $row): bool => $row->salaryComponent->is_basic);
        $basicSalary = round((float) ($basicSalaryRow?->amount ?? 0), 2);
        $items = collect();
        $issues = collect();

        if ($contract === null) {
            $issues->push($this->issue('error', 'no_active_contract', 'لا يوجد عقد يغطي فترة المسير.'));
        }

        if ($basicSalaryRow === null) {
            $issues->push($this->issue('error', 'missing_basic_salary', 'لا يوجد راتب أساسي ساري خلال فترة المسير.'));
        } else {
            $salaryRows->sortBy(fn (EmployeeSalaryComponent $row): int => $row->salaryComponent->sort_order)
                ->each(function (EmployeeSalaryComponent $row) use ($items, $basicSalary): void {
                    $component = $row->salaryComponent;
                    $amount = $component->calculation_type === SalaryComponent::CALCULATION_PERCENT_OF_BASIC
                        ? round($basicSalary * (float) $row->percentage / 100, 2)
                        : round((float) $row->amount, 2);
                    $formula = $component->calculation_type === SalaryComponent::CALCULATION_PERCENT_OF_BASIC
                        ? number_format($basicSalary, 2).' × '.number_format((float) $row->percentage, 2).'% = '.number_format($amount, 2)
                        : 'مبلغ ثابت: '.number_format($amount, 2);

                    $items->push($this->item(
                        $component->component_type,
                        $component->code,
                        $component->name_ar,
                        $amount,
                        $component->id,
                        $component->calculation_type === SalaryComponent::CALCULATION_PERCENT_OF_BASIC ? (float) $row->percentage : null,
                        $component->calculation_type === SalaryComponent::CALCULATION_PERCENT_OF_BASIC ? $basicSalary : null,
                        EmployeeSalaryComponent::class,
                        $row->id,
                        $formula,
                    ));
                });
        }

        $workedDays = $employee->attendance->whereIn('status', ['present', 'late'])->count();
        $absentDays = $employee->attendance->where('status', 'absent')->count();

        if ($absentDays > 0 && $basicSalary > 0) {
            $dailyRate = round($basicSalary / (float) config('payroll.monthly_days', 30), 2);
            $amount = round($absentDays * $dailyRate, 2);
            $items->push($this->item(
                'deduction',
                'absence',
                'خصم الغياب',
                $amount,
                quantity: $absentDays,
                rate: $dailyRate,
                formula: $absentDays.' × '.number_format($dailyRate, 2).' = '.number_format($amount, 2),
            ));
        }

        $employee->loans->flatMap->instalments->each(function (LoanInstalment $instalment) use ($items): void {
            $amount = round((float) $instalment->amount, 2);
            $items->push($this->item(
                'deduction',
                'loan',
                'قسط سلفة',
                $amount,
                sourceType: LoanInstalment::class,
                sourceId: $instalment->id,
                formula: 'القسط رقم '.$instalment->instalment_number.': '.number_format($amount, 2),
            ));
        });

        $employee->payrollAdjustments->each(function (PayrollAdjustment $adjustment) use ($items): void {
            $amount = round((float) $adjustment->amount, 2);
            $items->push($this->item(
                $adjustment->adjustment_type,
                'adjustment',
                $adjustment->adjustment_type === 'earning' ? 'استحقاق يدوي' : 'خصم يدوي',
                $amount,
                salaryComponentId: $adjustment->salary_component_id,
                sourceType: PayrollAdjustment::class,
                sourceId: $adjustment->id,
                formula: $adjustment->reason.': '.number_format($amount, 2),
            ));
        });

        if (blank($employee->iban)) {
            $issues->push($this->issue('warning', 'missing_iban', 'لا يوجد رقم آيبان مسجل للموظف.'));
        }

        if ($contract?->end_date?->betweenIncluded($periodStart, $periodEnd)) {
            $issues->push($this->issue('warning', 'contract_expires', 'ينتهي عقد الموظف خلال فترة المسير.'));
        }

        $totalEarnings = round((float) $items->where('item_type', 'earning')->sum('amount'), 2);
        $totalDeductions = round((float) $items->where('item_type', 'deduction')->sum('amount'), 2);
        $netSalary = round($totalEarnings - $totalDeductions, 2);

        if ($netSalary < 0) {
            $issues->push($this->issue('error', 'negative_net', 'تجاوزت الخصومات إجمالي الاستحقاقات؛ تم تثبيت الصافي عند صفر.'));
            $netSalary = 0;
        }

        $snapshot = PayrollRunEmployee::query()->create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'employee_name' => $employee->user->full_name,
            'department_name' => $employee->department?->name,
            'job_title_name' => $employee->jobTitle?->name,
            'port_name' => $employee->port?->name,
            'contract_type' => $contract?->contract_type,
            'basic_salary' => $basicSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'worked_days' => $workedDays,
            'absent_days' => $absentDays,
            'overtime_minutes' => 0,
            'status' => $issues->contains('level', 'error') ? 'error' : ($issues->contains('level', 'warning') ? 'warning' : 'ok'),
        ]);

        $snapshot->items()->createMany($items->all());
        $issues->each(fn (array $issue) => $run->issues()->create([...$issue, 'employee_id' => $employee->id]));
    }

    private function item(
        string $type,
        string $code,
        string $label,
        float $amount,
        ?int $salaryComponentId = null,
        ?float $quantity = null,
        ?float $rate = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        string $formula = '',
    ): array {
        return [
            'salary_component_id' => $salaryComponentId,
            'item_type' => $type,
            'code' => $code,
            'label_ar' => $label,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'calculation_details' => ['formula_ar' => $formula],
        ];
    }

    private function issue(string $level, string $code, string $message): array
    {
        return ['level' => $level, 'code' => $code, 'message_ar' => $message, 'resolved' => false];
    }
}
