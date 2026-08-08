<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The maintained city list is dropped: المدينة / المركز goes back to being typed by the
     * applicant, and `information_submissions.owner_city` keeps every city already filed.
     */
    public function up(): void
    {
        Schema::dropIfExists('cities');
    }

    public function down(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('governorate_id');
            $table->string('name', 150);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('governorate_id')->references('id')->on('governorates')->cascadeOnDelete();
            $table->unique(['governorate_id', 'name']);
        });
    }
};
