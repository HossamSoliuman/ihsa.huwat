<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'job_title',
                'department',
                'job_grade',
                'supervisor_name',
                'supervisor_phone',
                'contract_type',
                'contract_end_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('job_title', 190)->nullable()->after('national_id');
            $table->string('department', 190)->nullable()->after('job_title');
            $table->string('job_grade', 80)->nullable()->after('department');
            $table->string('supervisor_name', 190)->nullable()->after('job_grade');
            $table->string('supervisor_phone', 30)->nullable()->after('supervisor_name');
            $table->enum('contract_type', ['permanent', 'temporary'])->default('permanent')->after('hire_date');
            $table->date('contract_end_date')->nullable()->after('contract_type');
        });

        DB::table('employees')->orderBy('id')->get()->each(function (object $employee): void {
            $contract = DB::table('employee_contracts')
                ->where('employee_id', $employee->id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->latest('start_date')
                ->first();

            DB::table('employees')->where('id', $employee->id)->update([
                'job_title' => $employee->job_title_id === null ? null : DB::table('job_titles')->where('id', $employee->job_title_id)->value('name'),
                'department' => $employee->department_id === null ? null : DB::table('departments')->where('id', $employee->department_id)->value('name'),
                'supervisor_name' => $employee->manager_id === null
                    ? null
                    : DB::table('employees')->join('users', 'users.id', '=', 'employees.user_id')->where('employees.id', $employee->manager_id)->value('users.full_name'),
                'contract_type' => in_array($contract?->contract_type, ['permanent', 'temporary'], true) ? $contract->contract_type : 'temporary',
                'contract_end_date' => $contract?->end_date,
            ]);
        });
    }
};
