<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A market opens before its paperwork is complete: the investor is settled later, and
     * its محلات ودكات are laid out by count on the create screen and only then filled in
     * one by one from the market page. Both sides therefore start empty.
     */
    public function up(): void
    {
        Schema::table('fish_markets', function (Blueprint $table) {
            $table->string('investor_name', 190)->nullable()->change();
            $table->string('investor_commercial_registration_no', 60)->nullable()->change();
        });

        Schema::table('fish_market_units', function (Blueprint $table) {
            $table->string('entity_name', 190)->nullable()->change();
            $table->string('commercial_registration_no', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fish_markets', function (Blueprint $table) {
            $table->string('investor_name', 190)->nullable(false)->change();
            $table->string('investor_commercial_registration_no', 60)->nullable(false)->change();
        });

        Schema::table('fish_market_units', function (Blueprint $table) {
            $table->string('entity_name', 190)->nullable(false)->change();
            $table->string('commercial_registration_no', 60)->nullable(false)->change();
        });
    }
};
