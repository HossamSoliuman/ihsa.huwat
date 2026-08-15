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
            $table->string('user_email');
            $table->string('full_name')->nullable();
            $table->string('role')->default('user');
            $table->string('scope_level')->default('المملكة');
            $table->string('region')->nullable();
            $table->string('governorate')->nullable();
            $table->string('port')->nullable();
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('entity');
            $table->string('entity_id')->nullable();
            $table->string('user')->nullable();
            $table->string('user_role')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('ui_translations', function (Blueprint $table) {
            $table->id();
            $table->text('source_ar');
            $table->text('target_en')->nullable();
            $table->string('context')->nullable();
            $table->string('status')->default('مترجم');
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