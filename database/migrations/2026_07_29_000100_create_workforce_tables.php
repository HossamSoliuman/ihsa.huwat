<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedBigInteger('employment_application_id')->nullable()->unique();
            $table->string('employee_number', 40)->nullable()->unique();
            $table->string('national_id', 20)->nullable();
            $table->string('job_title', 190)->nullable();
            $table->string('department', 190)->nullable();
            $table->string('job_grade', 80)->nullable();
            $table->string('supervisor_name', 190)->nullable();
            $table->string('supervisor_phone', 30)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('contract_type', ['permanent', 'temporary'])->default('permanent');
            $table->date('contract_end_date')->nullable();
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])->default('active');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('name', ['morning', 'evening', 'night']);
            $table->time('start_time');
            $table->time('end_time');
        });

        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('port_id');
            $table->unsignedInteger('shift_id');
            $table->date('assignment_date');
            $table->boolean('is_temporary')->default(false);
            $table->unique(['employee_id', 'assignment_date'], 'uniq_employee_day');
            $table->index(['port_id', 'assignment_date']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('port_id')->references('id')->on('ports')->cascadeOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts');
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->date('attendance_date');
            $table->unsignedInteger('shift_id');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'on_leave'])->default('present');
            $table->unique(['employee_id', 'attendance_date', 'shift_id']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts');
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['employee_id', 'start_date', 'end_date']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('payroll', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->decimal('base_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->enum('paid_status', ['pending', 'paid'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->unique(['employee_id', 'period_month', 'period_year'], 'uniq_period');
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('employee_assignments');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employees');
    }
};
