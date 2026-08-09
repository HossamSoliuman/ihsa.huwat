<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('code', 40)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->change();
        });

        if (! collect(Schema::getIndexes('shifts'))->contains(fn (array $index): bool => $index['name'] === 'shifts_code_unique')) {
            Schema::table('shifts', function (Blueprint $table) {
                $table->unique('code');
            });
        }
    }
};
