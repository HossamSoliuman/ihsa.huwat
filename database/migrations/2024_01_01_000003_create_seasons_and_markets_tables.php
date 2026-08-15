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
            $table->string('species')->nullable();
            $table->string('region')->nullable();
            $table->string('season_type')->default('موسم صيد');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('gear_allowed')->nullable();
            $table->decimal('min_size_cm', 8, 2)->nullable();
            $table->decimal('quota_tons', 12, 2)->nullable();
            $table->string('status')->default('مغلق');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('season_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_number');
            $table->string('season')->nullable();
            $table->string('boat')->nullable();
            $table->string('captain')->nullable();
            $table->string('port')->nullable();
            $table->string('region')->nullable();
            $table->string('species')->nullable();
            $table->string('gear_type')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quota_kg', 12, 2)->nullable();
            $table->decimal('used_kg', 12, 2)->default(0);
            $table->string('status')->default('سارية');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('region')->nullable();
            $table->string('governorate')->nullable();
            $table->string('port')->nullable();
            $table->string('market_type')->default('مزاد');
            $table->unsignedInteger('fish_shops_count')->default(0);
            $table->unsignedInteger('auction_stalls_count')->default(0);
            $table->string('status')->default('نشط');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('market_auctions', function (Blueprint $table) {
            $table->id();
            $table->string('market')->nullable();
            $table->date('date')->nullable();
            $table->string('species')->nullable();
            $table->string('grade')->nullable();
            $table->decimal('offered_kg', 12, 2)->default(0);
            $table->decimal('sold_kg', 12, 2)->default(0);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->decimal('avg_price', 10, 2)->nullable();
            $table->string('buyer_type')->nullable();
            $table->string('source_port')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_auctions');
        Schema::dropIfExists('markets');
        Schema::dropIfExists('season_licenses');
        Schema::dropIfExists('fishing_seasons');
    }
};