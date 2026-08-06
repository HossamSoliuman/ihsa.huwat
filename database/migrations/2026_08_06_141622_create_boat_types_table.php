<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Boat types — the size band a registered boat falls in. */
    public function up(): void
    {
        Schema::create('boat_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 60)->unique();
            $table->string('name', 150)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boat_types');
    }
};
