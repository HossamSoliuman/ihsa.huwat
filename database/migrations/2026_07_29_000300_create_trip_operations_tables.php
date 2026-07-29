<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->increments('id');
            $table->string('trip_code', 30)->unique();
            $table->unsignedInteger('boat_id');
            $table->unsignedInteger('captain_id');
            $table->unsignedInteger('port_id');
            $table->unsignedInteger('assigned_employee_id')->nullable();
            $table->dateTime('expected_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->decimal('captain_reported_weight', 10, 2)->nullable();
            $table->decimal('verified_weight', 10, 2)->nullable();
            $table->enum('status', ['expected', 'arrived', 'waiting_employee', 'counting', 'pending_review', 'approved', 'closed'])->default('expected');
            $table->dateTime('counting_started_at')->nullable();
            $table->dateTime('counting_ended_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->boolean('edited_after_approval')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['port_id', 'status', 'actual_arrival']);
            $table->foreign('boat_id')->references('id')->on('boats');
            $table->foreign('captain_id')->references('id')->on('captains');
            $table->foreign('port_id')->references('id')->on('ports');
            $table->foreign('assigned_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('catch_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trip_id');
            $table->unsignedInteger('species_id');
            $table->decimal('captain_reported_kg', 10, 2)->default(0);
            $table->decimal('verified_kg', 10, 2)->default(0);
            $table->integer('boxes_count')->default(0);
            $table->boolean('is_unreported_by_captain')->default(false);
            $table->string('scale_photo_path')->nullable();
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
            $table->foreign('species_id')->references('id')->on('fish_species');
        });

        Schema::create('trip_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trip_id');
            $table->enum('type', ['scale_photo', 'captain_signature', 'other']);
            $table->string('file_path');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });

        Schema::create('trip_discrepancies', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trip_id');
            $table->decimal('diff_kg', 10, 2);
            $table->decimal('diff_percent', 5, 2);
            $table->enum('severity', ['minor', 'medium', 'major']);
            $table->string('reason')->nullable();
            $table->enum('review_status', ['pending', 'reviewed', 'approved'])->default('pending');
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->index(['review_status', 'severity']);
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 50);
            $table->string('message');
            $table->unsignedInteger('related_trip_id')->nullable();
            $table->unsignedInteger('related_port_id')->nullable();
            $table->unsignedInteger('related_employee_id')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();
            $table->index(['is_resolved', 'severity', 'created_at']);
            $table->foreign('related_trip_id')->references('id')->on('trips')->cascadeOnDelete();
            $table->foreign('related_port_id')->references('id')->on('ports')->cascadeOnDelete();
            $table->foreign('related_employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('trip_discrepancies');
        Schema::dropIfExists('trip_attachments');
        Schema::dropIfExists('catch_details');
        Schema::dropIfExists('trips');
    }
};
