<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $employees = DB::table('employees')->orderBy('id')->get();
        $usedEmployeeNumbers = DB::table('employees')->whereNotNull('employee_number')->pluck('employee_number')->flip()->all();
        $usedContractNumbers = DB::table('employee_contracts')->pluck('contract_number')->flip()->all();
        $nextEmployeeNumber = 1;
        $nextContractNumber = 1;

        foreach ($employees as $employee) {
            if ($employee->hire_date === null) {
                throw new RuntimeException("Employee {$employee->id} has no hire date; set it before running the employment backfill.");
            }

            $application = $employee->employment_application_id === null
                ? null
                : DB::table('employment_applications')->where('id', $employee->employment_application_id)->first();
            $portId = DB::table('employee_assignments')
                ->where('employee_id', $employee->id)
                ->latest('assignment_date')
                ->value('port_id') ?? $application?->preferred_port_id;

            $employeeNumber = filled($employee->employee_number)
                ? $employee->employee_number
                : $this->nextNumber(
                    (string) config('employment.employee_number_prefix', 'HWT'),
                    $usedEmployeeNumbers,
                    $nextEmployeeNumber,
                );

            DB::table('employees')->where('id', $employee->id)->update([
                'employee_number' => $employeeNumber,
                'nationality' => $this->nationalityCode($application?->nationality),
                'date_of_birth' => $application?->birth_date,
                'gender' => $application?->gender,
                'phone' => $application?->mobile,
                'email' => $application?->email ?? DB::table('users')->where('id', $employee->user_id)->value('email'),
                'department_id' => $this->lookupId('departments', $employee->department),
                'job_title_id' => $this->lookupId('job_titles', $employee->job_title),
                'manager_id' => $this->managerId($employee),
                'port_id' => $portId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_contracts')->insert([
                'employee_id' => $employee->id,
                'contract_number' => $this->nextNumber(
                    (string) config('employment.contract_number_prefix', 'HWT-C'),
                    $usedContractNumbers,
                    $nextContractNumber,
                ),
                'contract_type' => $employee->contract_type,
                'start_date' => $employee->hire_date,
                'end_date' => $employee->contract_end_date,
                'working_hours_per_day' => 8,
                'working_days_per_week' => 6,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The following schema rollback removes the copied records and columns.
    }

    private function lookupId(string $table, ?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $existingId = DB::table($table)->where('name', $name)->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        $code = Str::of($name)->slug('_')->limit(60, '')->toString();

        if ($code === '') {
            $code = 'legacy_'.Str::lower(Str::substr(hash('sha256', $name), 0, 12));
        }

        if (DB::table($table)->where('code', $code)->exists()) {
            $code = Str::limit($code, 47, '').'_'.Str::substr(hash('sha256', $name), 0, 12);
        }

        return (int) DB::table($table)->insertGetId([
            'code' => $code,
            'name' => $name,
            'sort_order' => (int) DB::table($table)->max('sort_order') + 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function managerId(object $employee): ?int
    {
        $supervisorName = trim((string) $employee->supervisor_name);

        if ($supervisorName === '') {
            return null;
        }

        $matches = DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->where('users.full_name', $supervisorName)
            ->where('employees.id', '<>', $employee->id)
            ->pluck('employees.id');

        return $matches->count() === 1 ? (int) $matches->first() : null;
    }

    private function nationalityCode(mixed $nationality): ?string
    {
        if ($nationality === null) {
            return null;
        }

        $nationalities = (array) config('information.nationalities', []);

        if (array_key_exists((string) $nationality, $nationalities)) {
            return (string) $nationality;
        }

        $code = array_search((string) $nationality, $nationalities, true);

        return $code === false ? null : (string) $code;
    }

    /** @param array<string, int> $usedNumbers */
    private function nextNumber(string $prefix, array &$usedNumbers, int &$next): string
    {
        do {
            $number = $prefix.'-'.str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (array_key_exists($number, $usedNumbers));

        $usedNumbers[$number] = 1;

        return $number;
    }
};
