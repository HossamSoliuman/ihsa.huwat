<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Types of gear declared on a submission — nets, traps, lines. */
    public function up(): void
    {
        Schema::create('fishing_tool_types', function (Blueprint $table) {
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
        Schema::dropIfExists('fishing_tool_types');
    }
};
