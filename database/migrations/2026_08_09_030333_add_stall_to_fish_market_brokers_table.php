<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A دلال works out of a دكة حراج, which is already a record on the market it belongs to,
     * so the attachment is the unit itself rather than its name typed again. `stall_number`
     * is the number painted on the stall, which is not always the order it was laid out in.
     * Both stay optional: a market may hold no دكات yet, and a broker filed before the
     * stalls were registered keeps its record.
     */
    public function up(): void
    {
        Schema::table('fish_market_brokers', function (Blueprint $table) {
            $table->unsignedInteger('fish_market_unit_id')->nullable()->after('fish_market_id');
            $table->string('stall_number', 20)->nullable()->after('fish_market_unit_id');
            $table->index('fish_market_unit_id', 'idx_fish_market_brokers_unit');
            $table->foreign('fish_market_unit_id')->references('id')->on('fish_market_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fish_market_brokers', function (Blueprint $table) {
            $table->dropForeign(['fish_market_unit_id']);
            $table->dropIndex('idx_fish_market_brokers_unit');
            $table->dropColumn(['fish_market_unit_id', 'stall_number']);
        });
    }
};
