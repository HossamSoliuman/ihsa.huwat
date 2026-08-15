<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique();
            $table->foreignId('boat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('departure_port_id')->constrained('ports')->cascadeOnDelete();
            $table->string('captain_name')->nullable();
            $table->unsignedInteger('crew_count')->default(0);
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('return_time')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();
            $table->string('gear_type')->nullable();
            $table->decimal('captain_input_kg', 12, 2)->nullable();
            $table->decimal('actual_weight_kg', 12, 2)->nullable();
            $table->decimal('diff_kg', 12, 2)->nullable();
            $table->decimal('approved_kg', 12, 2)->nullable();
            $table->string('status')->default('مجدولة');
            $table->string('statistics_officer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('catch_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_kg', 12, 2)->default(0);
            $table->decimal('avg_weight_kg', 8, 2)->nullable();
            $table->decimal('price_per_kg', 8, 2)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();
            $table->date('recorded_at');
            $table->timestamps();
        });

        Schema::create('bycatch_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('species_name');
            $table->decimal('quantity_kg', 12, 2)->default(0);
            $table->string('action_taken')->nullable();
            $table->string('status')->default('مسجل');
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->string('severity')->default('متوسط');
            $table->string('region')->nullable();
            $table->string('port')->nullable();
            $table->string('boat')->nullable();
            $table->string('species')->nullable();
            $table->text('description')->nullable();
            $table->date('date')->nullable();
            $table->string('status')->default('جديدة');
            $table->timestamps();
        });

        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('violation_type');
            $table->string('severity')->default('متوسط');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('fine_amount', 12, 2)->nullable();
            $table->string('action')->nullable();
            $table->date('date')->nullable();
            $table->string('status')->default('مسجلة');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violations');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('bycatch_records');
        Schema::dropIfExists('catch_records');
        Schema::dropIfExists('trips');
    }
};