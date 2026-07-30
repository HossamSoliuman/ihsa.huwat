<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->integer('region_id')->index();
            $table->string('name', 120);
            $table->string('status', 20)->default('upcoming')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->json('fishing_tools');
            $table->unsignedInteger('licenses_count')->default(0);
            $table->decimal('minimum_size', 8, 2)->nullable();
            $table->decimal('maximum_size', 8, 2)->nullable();
            $table->text('restrictions');
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
