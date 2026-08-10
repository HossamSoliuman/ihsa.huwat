<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a moderator account may reach. Every row names one record — a region, a
     * governorate, a port or a market — and an account holds as many rows as it was given,
     * all at the same level. No account with rows here ever sees past them.
     *
     * `scope_id` addresses four different tables, so it carries no foreign key; a record
     * deleted underneath leaves a row that resolves to nothing, which is what it means.
     */
    public function up(): void
    {
        Schema::create('user_scopes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->enum('scope_type', ['region', 'governorate', 'port', 'market']);
            $table->unsignedInteger('scope_id');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
    }
};
