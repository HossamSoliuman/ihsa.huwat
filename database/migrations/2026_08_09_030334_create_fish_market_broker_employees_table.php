<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The موظفون of a دلال are counted, not named: the desk registers how many of each
     * وظيفة and جنسية work for him. `job_title` and `nationality` hold the code of their
     * option list, so a renamed option leaves the record readable, and the pair is unique
     * per broker because two rows for the same pair are one count split in two.
     */
    public function up(): void
    {
        Schema::create('fish_market_broker_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fish_market_broker_id');
            $table->string('job_title', 60);
            $table->string('nationality', 60);
            $table->unsignedSmallInteger('headcount');
            $table->timestamps();
            $table->unique(['fish_market_broker_id', 'job_title', 'nationality'], 'idx_broker_employees_unique_pair');
            $table->foreign('fish_market_broker_id')->references('id')->on('fish_market_brokers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_market_broker_employees');
    }
};
