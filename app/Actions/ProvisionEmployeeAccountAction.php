<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmploymentApplication;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProvisionEmployeeAccountAction
{
    public function __construct(
        public CreateEmployeeContractAction $createEmployeeContract,
        public RecordAuditLogAction $recordAuditLog,
    ) {}

    public function execute(EmploymentApplication $application, array $attributes, User $actor, ?string $ipAddress = null): Employee
    {
        return DB::transaction(function () use ($application, $attributes, $actor, $ipAddress): Employee {
            $lockedApplication = EmploymentApplication::query()->with('job')->lockForUpdate()->findOrFail($application->id);

            if ($lockedApplication->status !== 'accepted') {
                throw ValidationException::withMessages(['application' => 'لا يمكن إنشاء الحساب إلا بعد اعتماد الطلب بالحالة «مقبول».']);
            }

            if ($lockedApplication->employee_user_id !== null) {
                throw ValidationException::withMessages(['application' => 'تم إنشاء حساب لهذا الطلب مسبقًا.']);
            }

            if (User::query()->where('email', $lockedApplication->email)->exists()) {
                throw ValidationException::withMessages(['email' => 'البريد الإلكتروني للمتقدم مرتبط بحساب مستخدم موجود بالفعل.']);
            }

            $employeeRole = Role::query()->where('code', 'employee_portal')->first();

            if ($employeeRole === null) {
                throw ValidationException::withMessages(['role' => 'دور بوابة الموظف غير مهيأ.']);
            }

            $nationality = $this->nationalityCode($lockedApplication->nationality);

            if ($nationality === null) {
                throw ValidationException::withMessages(['nationality' => 'جنسية المتقدم غير موجودة في قائمة الجنسيات.']);
            }

            Employee::query()->lockForUpdate()->get(['id']);

            $user = User::query()->create([
                'role_id' => $employeeRole->id,
                'full_name' => $lockedApplication->full_name,
                'username' => $attributes['username'],
                'email' => $lockedApplication->email,
                'password_hash' => Hash::make($attributes['password']),
                'port_id' => $attributes['port_id'] ?? null,
                'is_active' => true,
            ]);

            $employee = Employee::query()->create([
                'user_id' => $user->id,
                'employment_application_id' => $lockedApplication->id,
                'employee_number' => $this->nextEmployeeNumber(),
                'national_id' => mb_strlen($lockedApplication->identity_number) <= 20 ? $lockedApplication->identity_number : null,
                'nationality' => $nationality,
                'date_of_birth' => $lockedApplication->birth_date,
                'gender' => $lockedApplication->gender,
                'phone' => $lockedApplication->mobile,
                'email' => $lockedApplication->email,
                'department_id' => $attributes['department_id'],
                'job_title_id' => $attributes['job_title_id'],
                'manager_id' => $attributes['manager_id'] ?? null,
                'port_id' => $attributes['port_id'] ?? $lockedApplication->preferred_port_id,
                'hire_date' => $attributes['hire_date'],
                'status' => 'active',
            ]);

            $this->createEmployeeContract->execute($employee, [
                'contract_type' => $attributes['contract_type'],
                'start_date' => $attributes['contract_start_date'],
                'end_date' => $attributes['contract_end_date'] ?? null,
                'probation_end_date' => $attributes['probation_end_date'] ?? null,
                'working_hours_per_day' => $attributes['working_hours_per_day'],
                'working_days_per_week' => $attributes['working_days_per_week'],
                'status' => 'active',
            ], $actor, $ipAddress);

            $basicSalaryComponent = SalaryComponent::query()->where('code', 'basic')->firstOrFail();
            $salary = EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->id,
                'salary_component_id' => $basicSalaryComponent->id,
                'amount' => $attributes['base_salary'],
                'effective_from' => $attributes['hire_date'],
                'created_by' => $actor->id,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'employee_provisioned',
                $employee,
                newValues: $employee->only(['employee_number', 'department_id', 'job_title_id', 'hire_date', 'status']),
                ipAddress: $ipAddress,
            );
            $this->recordAuditLog->execute(
                $actor,
                'salary_component_created',
                $salary,
                newValues: $salary->only(['salary_component_id', 'amount', 'effective_from']),
                ipAddress: $ipAddress,
            );

            if (isset($attributes['port_id'], $attributes['shift_id'])) {
                $employee->assignments()->create([
                    'port_id' => $attributes['port_id'],
                    'shift_id' => $attributes['shift_id'],
                    'assignment_date' => $attributes['hire_date'],
                    'is_temporary' => false,
                ]);
            }

            $lockedApplication->forceFill([
                'status' => 'account_created',
                'employee_user_id' => $user->id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            $lockedApplication->events()->create([
                'event_type' => 'account_created',
                'from_status' => 'accepted',
                'to_status' => 'account_created',
                'note' => 'تم إنشاء حساب الموظف وربطه بالرقم الوظيفي '.$employee->employee_number.'.',
                'actor_user_id' => $actor->id,
            ]);

            return $employee->load('user');
        });
    }

    private function nationalityCode(string $value): ?string
    {
        return Nationality::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('code', $value)->orWhere('name', $value))
            ->value('code');
    }

    private function nextEmployeeNumber(): string
    {
        $prefix = (string) config('employment.employee_number_prefix', 'HWT');
        $lastNumber = Employee::query()->where('employee_number', 'like', $prefix.'-%')->orderByDesc('employee_number')->value('employee_number');
        $sequence = $lastNumber === null ? 1 : ((int) substr($lastNumber, strlen($prefix) + 1)) + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
        } while (Employee::query()->where('employee_number', $number)->exists());

        return $number;
    }
}
