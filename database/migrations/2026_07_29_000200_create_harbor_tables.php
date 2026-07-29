<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->string('registration_no', 50)->nullable()->unique();
            $table->enum('boat_type', ['large', 'small', 'recreational', 'unclassified'])->default('unclassified');
            $table->enum('harbor_status', ['occupied', 'disabled', 'inactive', 'unclassified'])->default('unclassified');
            $table->unsignedInteger('home_port_id')->nullable();
            $table->foreign('home_port_id')->references('id')->on('ports')->nullOnDelete();
        });

        Schema::create('harbor_boat_capacities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('port_id');
            $table->enum('boat_type', ['large', 'small', 'recreational']);
            $table->unsignedInteger('capacity')->default(0);
            $table->enum('status', ['available', 'full', 'stopped'])->default('available');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['port_id', 'boat_type'], 'uniq_port_boat_type');
            $table->foreign('port_id')->references('id')->on('ports')->cascadeOnDelete();
        });

        Schema::create('harbor_workers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('port_id');
            $table->string('employee_name', 150);
            $table->string('identity_number')->nullable();
            $table->enum('nationality', ['saudi', 'non_saudi'])->default('saudi');
            $table->enum('worker_type', ['supervisor', 'contractor', 'fisherman', 'foreign_worker']);
            $table->string('mobile_number', 30)->nullable();
            $table->enum('employment_status', ['active', 'suspended', 'expired'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['port_id', 'worker_type', 'employment_status'], 'idx_harbor_workers_port_type');
            $table->foreign('port_id')->references('id')->on('ports')->cascadeOnDelete();
        });

        Schema::create('harbor_licenses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('port_id');
            $table->string('license_number', 80)->unique();
            $table->enum('license_type', ['seasonal', 'operational'])->default('seasonal');
            $table->string('license_holder_name', 190);
            $table->string('boat_number', 80)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('license_status', ['valid', 'expired', 'suspended', 'cancelled'])->default('valid');
            $table->string('attachment_path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['port_id', 'license_type', 'license_status'], 'idx_harbor_licenses_port_type');
            $table->foreign('port_id')->references('id')->on('ports')->cascadeOnDelete();
        });

        Schema::create('captains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name', 150);
            $table->string('national_id', 20)->nullable();
            $table->string('phone', 20)->nullable();
        });

        Schema::create('fish_species', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name_ar', 150)->unique();
        });

        Schema::create('harbor_violations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('port_id');
            $table->string('violation_number', 80)->unique();
            $table->string('violation_type', 120);
            $table->text('violation_description')->nullable();
            $table->dateTime('violation_date');
            $table->unsignedInteger('boat_id')->nullable();
            $table->string('boat_owner_name', 190)->nullable();
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->enum('violation_status', ['open', 'paid', 'appealed', 'closed'])->default('open');
            $table->unsignedInteger('created_by')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['port_id', 'violation_status'], 'idx_harbor_violations_port_status');
            $table->foreign('port_id')->references('id')->on('ports')->cascadeOnDelete();
            $table->foreign('boat_id')->references('id')->on('boats')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harbor_violations');
        Schema::dropIfExists('fish_species');
        Schema::dropIfExists('captains');
        Schema::dropIfExists('harbor_licenses');
        Schema::dropIfExists('harbor_workers');
        Schema::dropIfExists('harbor_boat_capacities');
        Schema::dropIfExists('boats');
    }
};
