<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fishing_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('species');
            $table->string('sea')->nullable();
            $table->string('region')->nullable();
            $table->unsignedTinyInteger('start_month')->nullable();
            $table->unsignedTinyInteger('end_month')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('ban_start_date')->nullable();
            $table->date('ban_end_date')->nullable();
            $table->string('gear')->nullable();
            $table->string('gear_type')->nullable();
            $table->string('license_type')->nullable();
            $table->unsignedInteger('licenses_max')->default(0);
            $table->unsignedInteger('licenses_issued')->default(0);
            $table->unsignedInteger('licenses_active')->default(0);
            $table->unsignedInteger('boats_count')->default(0);
            $table->unsignedInteger('min_size_cm')->nullable();
            $table->text('allowed_areas')->nullable();
            $table->text('prohibited_areas')->nullable();
            $table->string('authority')->nullable();
            $table->string('decision_number')->nullable();
            $table->date('decision_date')->nullable();
            $table->string('decision_document_url', 500)->nullable();
            $table->decimal('quota_tons', 12, 2)->default(0);
            $table->decimal('used_quota_tons', 12, 2)->default(0);
            $table->string('approval_status')->default('مسودة');
            $table->string('status')->default('مغلق');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('season_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fishing_season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_number')->unique();
            $table->string('boat_name');
            $table->string('fisher_name')->nullable();
            $table->string('holder_name')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('gear_type')->nullable();
            $table->text('allowed_area')->nullable();
            $table->decimal('quota_kg', 12, 2)->default(0);
            $table->decimal('used_kg', 12, 2)->default(0);
            $table->string('status')->default('سارية');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_licenses');
        Schema::dropIfExists('fishing_seasons');
    }
};