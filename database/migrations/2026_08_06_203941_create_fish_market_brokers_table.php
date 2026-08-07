<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الدلالين attached to a market. `entity_type` decides which fieldset is required, so
     * it stays an enum rather than an option list: it is branch logic, not a value an
     * administrator picks from a maintained list. `phone` is shared — رقم الجوال for a
     * فرد, التليفون for a منشأة.
     */
    public function up(): void
    {
        Schema::create('fish_market_brokers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fish_market_id');
            $table->enum('entity_type', ['individual', 'establishment']);
            $table->string('full_name', 150)->nullable();
            $table->string('nationality', 60)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('job_title', 60)->nullable();
            $table->string('entity_name', 190)->nullable();
            $table->string('commercial_registration_no', 60)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->string('national_address', 190)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['fish_market_id', 'entity_type'], 'idx_fish_market_brokers_market_type');
            $table->foreign('fish_market_id')->references('id')->on('fish_markets')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_market_brokers');
    }
};
