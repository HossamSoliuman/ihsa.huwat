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
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->decimal('coast_length_km', 8, 2)->default(0);
            $table->unsignedInteger('governorates_count')->default(0);
            $table->unsignedInteger('ports_count')->default(0);
            $table->decimal('total_catch_tons', 12, 2)->default(0);
            $table->unsignedInteger('active_boats')->default(0);
            $table->unsignedInteger('active_fishers')->default(0);
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->unsignedInteger('ports_count')->default(0);
            $table->unsignedInteger('active_boats')->default(0);
            $table->unsignedInteger('active_fishers')->default(0);
            $table->decimal('total_catch_tons', 12, 2)->default(0);
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('code')->nullable();
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
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('site_type')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('pressure_level')->default('طبيعي');
            $table->decimal('catch_kg', 12, 2)->default(0);
            $table->string('status')->default('نشط');
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