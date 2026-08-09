<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('run_number', 60)->unique();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date')->nullable();
            $table->unsignedInteger('employees_count')->default(0);
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_total', 14, 2)->default(0);
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'closed'])->default('draft');
            $table->string('payment_reference')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
            $table->dateTime('calculated_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
            $table->index(['status', 'period_year', 'period_month']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('payroll_run_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_run_id');
            $table->unsignedInteger('employee_id');
            $table->string('employee_number', 40);
            $table->string('employee_name', 190);
            $table->string('department_name', 150)->nullable();
            $table->string('job_title_name', 150)->nullable();
            $table->string('port_name', 150)->nullable();
            $table->string('contract_type', 40)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->unsignedSmallInteger('worked_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->enum('status', ['ok', 'warning', 'error'])->default('ok');
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index(['employee_id', 'payroll_run_id']);
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
        });

        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_run_employee_id');
            $table->unsignedInteger('salary_component_id')->nullable();
            $table->enum('item_type', ['earning', 'deduction']);
            $table->string('code', 60);
            $table->string('label_ar', 190);
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->json('calculation_details');

            $table->index('payroll_run_employee_id');
            $table->index(['source_type', 'source_id']);
            $table->foreign('payroll_run_employee_id')->references('id')->on('payroll_run_employees')->cascadeOnDelete();
            $table->foreign('salary_component_id')->references('id')->on('salary_components')->nullOnDelete();
        });

        Schema::create('payroll_run_issues', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payroll_run_id');
            $table->unsignedInteger('employee_id')->nullable();
            $table->enum('level', ['error', 'warning']);
            $table->string('code', 60);
            $table->text('message_ar');
            $table->boolean('resolved')->default(false);
            $table->timestamps();

            $table->index(['payroll_run_id', 'level', 'resolved']);
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->nullOnDelete();
        });

        Schema::table('loan_instalments', function (Blueprint $table) {
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_adjustments', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
        });

        Schema::table('loan_instalments', function (Blueprint $table) {
            $table->dropForeign(['payroll_run_id']);
        });

        Schema::dropIfExists('payroll_run_issues');
        Schema::dropIfExists('payroll_run_items');
        Schema::dropIfExists('payroll_run_employees');
        Schema::dropIfExists('payroll_runs');
    }
};
