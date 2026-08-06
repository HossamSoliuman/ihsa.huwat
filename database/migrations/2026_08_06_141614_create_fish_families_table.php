<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The families of the national catch coding sheet — every code that ends in "00",
     * such as 1500 Serranidae. Species carry the codes filed under them, so the whole
     * catalogue can be rolled up per family without parsing the code in application code.
     */
    public function up(): void
    {
        Schema::create('fish_families', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('code')->unique();
            $table->string('scientific_name', 150);
            $table->string('english_name', 190)->nullable();
            $table->string('local_name_gulf', 190)->nullable();
            $table->string('local_name_red_sea', 190)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_families');
    }
};
