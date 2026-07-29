<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('governorates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('region_id');
            $table->string('name', 150);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('governorate_id');
            $table->string('name', 150);
            $table->string('location_name', 190)->nullable();
            $table->string('location_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('governorate_id')->references('id')->on('governorates')->cascadeOnDelete();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('dashboard_route', 100);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id');
            $table->string('full_name', 150);
            $table->string('username', 100)->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password_hash');
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('governorate_id')->nullable();
            $table->unsignedInteger('port_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->foreign('governorate_id')->references('id')->on('governorates')->nullOnDelete();
            $table->foreign('port_id')->references('id')->on('ports')->nullOnDelete();
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 100);
            $table->string('ip_address', 45);
            $table->boolean('success');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['username', 'success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('governorates');
        Schema::dropIfExists('regions');
    }
};
