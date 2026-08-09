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
            $table->string('nationality', 60)->nullable()->after('national_id');
            $table->date('date_of_birth')->nullable()->after('nationality');
            $table->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
            $table->string('phone', 30)->nullable()->after('gender');
            $table->string('email', 190)->nullable()->after('phone');
            $table->unsignedInteger('department_id')->nullable()->after('email');
            $table->unsignedInteger('job_title_id')->nullable()->after('department_id');
            $table->unsignedInteger('manager_id')->nullable()->after('job_title_id');
            $table->unsignedInteger('port_id')->nullable()->after('manager_id');
            $table->unsignedInteger('bank_id')->nullable()->after('port_id');
            $table->string('iban', 34)->nullable()->after('bank_id');
            $table->string('account_holder_name')->nullable()->after('iban');
            $table->date('termination_date')->nullable()->after('status');
            $table->text('termination_reason')->nullable()->after('termination_date');
            $table->timestamps();

            $table->enum('status', ['draft', 'active', 'on_leave', 'suspended', 'terminated', 'inactive'])
                ->default('active')
                ->change();

            $table->index('national_id');
            $table->index('status');
            $table->index('port_id');
            $table->index('department_id');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('job_title_id')->references('id')->on('job_titles');
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('port_id')->references('id')->on('ports')->nullOnDelete();
            $table->foreign('bank_id')->references('id')->on('banks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('employees')->whereIn('status', ['draft', 'inactive'])->update(['status' => 'active']);

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['job_title_id']);
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['port_id']);
            $table->dropForeign(['bank_id']);
            $table->dropIndex(['national_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['port_id']);
            $table->dropIndex(['department_id']);
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])
                ->default('active')
                ->change();
            $table->dropColumn([
                'nationality',
                'date_of_birth',
                'gender',
                'phone',
                'email',
                'department_id',
                'job_title_id',
                'manager_id',
                'port_id',
                'bank_id',
                'iban',
                'account_holder_name',
                'termination_date',
                'termination_reason',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
