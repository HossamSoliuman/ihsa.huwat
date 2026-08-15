<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->string('region');
            $table->string('governorate');
            $table->string('port')->nullable();
            $table->string('market_type')->default('مزاد');
            $table->unsignedInteger('fish_shops_count')->default(0);
            $table->unsignedInteger('auction_stalls_count')->default(0);
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('market_auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->date('auction_date');
            $table->decimal('quantity_offered_kg', 12, 2)->default(0);
            $table->decimal('quantity_sold_kg', 12, 2)->default(0);
            $table->decimal('avg_price_per_kg', 8, 2)->default(0);
            $table->string('buyer_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_auctions');
        Schema::dropIfExists('markets');
    }
};