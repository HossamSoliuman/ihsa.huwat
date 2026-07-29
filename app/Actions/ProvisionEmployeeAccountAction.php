<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmploymentApplication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProvisionEmployeeAccountAction
{
    public function execute(EmploymentApplication $application, array $attributes, User $actor): Employee
    {
        return DB::transaction(function () use ($application, $attributes, $actor): Employee {
            $lockedApplication = EmploymentApplication::query()
                ->with('job')
                ->lockForUpdate()
                ->findOrFail($application->id);

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
                'employee_number' => $attributes['employee_number'],
                'national_id' => mb_strlen($lockedApplication->identity_number) <= 20 ? $lockedApplication->identity_number : null,
                'job_title' => $attributes['job_title'],
                'department' => $attributes['department'] ?? null,
                'job_grade' => $attributes['job_grade'] ?? null,
                'supervisor_name' => $attributes['supervisor_name'] ?? null,
                'supervisor_phone' => $attributes['supervisor_phone'] ?? null,
                'hire_date' => $attributes['hire_date'],
                'contract_type' => $attributes['contract_type'],
                'contract_end_date' => $attributes['contract_end_date'] ?? null,
                'base_salary' => $attributes['base_salary'] ?? 0,
                'status' => 'active',
            ]);

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
}
