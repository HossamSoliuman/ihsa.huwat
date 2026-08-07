<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * العمالة working a shop or an auction stall. `nationality` and `job_title` keep the
     * code of their option list, never the Arabic name, so a renamed option stays
     * readable on the records already filed under it.
     */
    public function up(): void
    {
        Schema::create('fish_market_workers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fish_market_unit_id');
            $table->string('full_name', 150);
            $table->string('national_id', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('nationality', 60);
            $table->string('job_title', 60);
            $table->timestamps();
            $table->index('fish_market_unit_id', 'idx_fish_market_workers_unit');
            $table->foreign('fish_market_unit_id')->references('id')->on('fish_market_units')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_market_workers');
    }
};
