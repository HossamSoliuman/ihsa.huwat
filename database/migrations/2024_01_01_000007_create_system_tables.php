<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->unique();
            $table->string('role')->default('user');
            $table->string('region')->nullable();
            $table->string('governorate')->nullable();
            $table->string('port')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->nullable();
            $table->string('role')->nullable();
            $table->string('action');
            $table->string('entity')->nullable();
            $table->string('record_label')->nullable();
            $table->text('details')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();
        });

        Schema::create('ui_translations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('ar');
            $table->text('en')->nullable();
            $table->string('context')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        Schema::dropIfExists('ui_translations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_permissions');
    }
};