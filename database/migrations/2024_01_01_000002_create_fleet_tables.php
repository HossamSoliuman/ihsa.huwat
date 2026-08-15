<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('code')->nullable()->unique();
            $table->string('name_ar')->unique();
            $table->string('name_sci')->nullable();
            $table->string('name_en')->nullable();
            $table->string('corrected_name_sci')->nullable();
            $table->string('name_local_gulf')->nullable();
            $table->string('name_local_red_sea')->nullable();
            $table->string('record_type')->default('نوع سمك');
            $table->string('category')->default('أسماك');
            $table->decimal('avg_weight_kg', 8, 2)->nullable();
            $table->decimal('avg_length_cm', 8, 2)->nullable();
            $table->string('season')->nullable();
            $table->string('regions')->nullable();
            $table->decimal('catch_kg', 14, 2)->default(0);
            $table->unsignedInteger('trips_count')->default(0);
            $table->unsignedInteger('boats_count')->default(0);
            $table->string('top_port')->nullable();
            $table->string('status')->default('مستقر');
            $table->string('directory_status')->default('نشط');
            $table->string('review_status')->nullable();
            $table->string('problem_type')->nullable();
            $table->string('source_1')->nullable();
            $table->string('source_2')->nullable();
            $table->date('review_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('gear_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('category')->nullable();
            $table->string('selectivity')->nullable();
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('boat_number')->unique();
            $table->string('boat_type')->nullable();
            $table->decimal('length_m', 6, 2)->nullable();
            $table->string('owner')->nullable();
            $table->string('captain')->nullable();
            $table->unsignedInteger('crew_count')->default(0);
            $table->decimal('total_catch_kg', 14, 2)->default(0);
            $table->string('license_status')->default('سارية');
            $table->string('status')->default('نشط');
            $table->unsignedInteger('violations_count')->default(0);
            $table->timestamps();
        });

        Schema::create('fishers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('national_id')->unique();
            $table->string('license_number')->nullable();
            $table->string('license_status')->default('سارية');
            $table->string('role')->default('صياد');
            $table->string('phone')->nullable();
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('statistics_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('employee_number')->unique();
            $table->string('shift')->default('صباحية');
            $table->unsignedInteger('trips_counted')->default(0);
            $table->string('status')->default('نشط');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistics_officers');
        Schema::dropIfExists('fishers');
        Schema::dropIfExists('boats');
        Schema::dropIfExists('gear_types');
        Schema::dropIfExists('species');
    }
};