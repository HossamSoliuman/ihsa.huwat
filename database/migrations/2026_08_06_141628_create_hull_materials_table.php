<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Materials a hull is built from. */
    public function up(): void
    {
        Schema::create('hull_materials', function (Blueprint $table) {
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
        Schema::dropIfExists('hull_materials');
    }
};
