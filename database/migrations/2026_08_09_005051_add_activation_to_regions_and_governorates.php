<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * المناطق and المحافظات join الموانئ in being switched off rather than deleted, so a
     * region the portal should stop offering can be retired without touching the records
     * already filed under it.
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::table('governorates', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
