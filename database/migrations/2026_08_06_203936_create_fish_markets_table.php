<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A fish market and the investor holding it. The region is read through the
     * governorate, exactly as `ports` does, so no `region_id` is stored here.
     */
    public function up(): void
    {
        Schema::create('fish_markets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('governorate_id');
            $table->string('name', 150);
            $table->string('investor_name', 190);
            $table->string('investor_commercial_registration_no', 60);
            $table->string('investor_phone', 30)->nullable();
            $table->string('investor_email', 190)->nullable();
            $table->string('investor_tax_number', 60)->nullable();
            $table->string('investor_national_address', 190)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['governorate_id', 'name'], 'uniq_fish_market_governorate_name');
            $table->foreign('governorate_id')->references('id')->on('governorates')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_markets');
    }
};
