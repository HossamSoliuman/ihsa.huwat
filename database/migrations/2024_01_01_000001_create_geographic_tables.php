<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('coast_length_km', 10, 2)->nullable();
            $table->unsignedInteger('governorates_count')->default(0);
            $table->unsignedInteger('ports_count')->default(0);
            $table->decimal('total_catch_tons', 12, 2)->default(0);
            $table->unsignedInteger('active_boats')->default(0);
            $table->unsignedInteger('active_fishers')->default(0);
            $table->timestamps();
        });

        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->nullable();
            $table->boolean('coastal')->default(true);
            $table->unsignedInteger('ports_count')->default(0);
            $table->decimal('total_catch_tons', 12, 2)->default(0);
            $table->unsignedInteger('active_boats')->default(0);
            $table->unsignedInteger('active_fishers')->default(0);
            $table->timestamps();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('region')->nullable();
            $table->string('governorate')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->unsignedInteger('boats_count')->default(0);
            $table->unsignedInteger('active_boats')->default(0);
            $table->unsignedInteger('fishers_count')->default(0);
            $table->unsignedInteger('daily_trips')->default(0);
            $table->unsignedInteger('monthly_trips')->default(0);
            $table->decimal('total_catch_tons', 12, 2)->default(0);
            $table->unsignedInteger('statistics_staff')->default(0);
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('fishing_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('region')->nullable();
            $table->string('nearest_port')->nullable();
            $table->decimal('depth_m', 8, 2)->nullable();
            $table->unsignedInteger('trips_count')->default(0);
            $table->unsignedInteger('boats_count')->default(0);
            $table->decimal('catch_kg', 12, 2)->default(0);
            $table->decimal('avg_catch_per_trip', 10, 2)->default(0);
            $table->string('pressure_level')->default('طبيعي');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fishing_sites');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('governorates');
        Schema::dropIfExists('regions');
    }
};