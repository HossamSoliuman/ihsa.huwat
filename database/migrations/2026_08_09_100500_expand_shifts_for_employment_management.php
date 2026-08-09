<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('name', 60)->change();
            $table->string('code', 40)->nullable()->unique()->after('id');
            $table->boolean('crosses_midnight')->default(false)->after('end_time');
            $table->unsignedSmallInteger('grace_minutes')->default(15)->after('crosses_midnight');
            $table->boolean('is_active')->default(true)->after('grace_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'crosses_midnight', 'grace_minutes', 'is_active']);
            $table->enum('name', ['morning', 'evening', 'night'])->change();
        });
    }
};
