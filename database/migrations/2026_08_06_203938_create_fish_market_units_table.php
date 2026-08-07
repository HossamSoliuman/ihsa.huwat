<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * محلات بيع السمك and دكات الحراجات carry the same commercial-entity fields and the
     * same عمالة block, so they share one table and are told apart by `unit_type`.
     */
    public function up(): void
    {
        Schema::create('fish_market_units', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fish_market_id');
            $table->enum('unit_type', ['shop', 'auction_stall']);
            $table->string('label', 100)->nullable();
            $table->string('entity_name', 190);
            $table->string('commercial_registration_no', 60);
            $table->string('municipality_licence_no', 60)->nullable();
            $table->string('national_address', 190)->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['fish_market_id', 'unit_type'], 'idx_fish_market_units_market_type');
            $table->foreign('fish_market_id')->references('id')->on('fish_markets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_market_units');
    }
};
