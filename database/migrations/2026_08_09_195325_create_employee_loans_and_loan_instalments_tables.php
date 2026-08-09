<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('employee_id');
            $table->string('loan_number', 60)->unique();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('instalments_count');
            $table->decimal('instalment_amount', 12, 2);
            $table->date('first_instalment_month');
            $table->text('reason');
            $table->enum('status', ['requested', 'approved', 'active', 'completed', 'cancelled'])->default('requested');
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('loan_instalments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('loan_id');
            $table->unsignedSmallInteger('instalment_number');
            $table->unsignedSmallInteger('due_year');
            $table->unsignedTinyInteger('due_month');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->unsignedInteger('payroll_run_id')->nullable();
            $table->enum('status', ['scheduled', 'deducted', 'waived'])->default('scheduled');
            $table->timestamps();

            $table->unique(['loan_id', 'instalment_number']);
            $table->index(['due_year', 'due_month', 'status']);
            $table->foreign('loan_id')->references('id')->on('employee_loans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_instalments');
        Schema::dropIfExists('employee_loans');
    }
};
