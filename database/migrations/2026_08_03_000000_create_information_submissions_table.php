<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference_no', 30)->unique();
            $table->unsignedInteger('submitted_by')->nullable();
            $table->unsignedInteger('port_id')->nullable();
            $table->unsignedInteger('boat_id')->nullable();
            $table->unsignedInteger('captain_id')->nullable();
            $table->string('owner_full_name', 150);
            $table->string('owner_national_id', 20)->index();
            $table->string('owner_phone', 20);
            $table->unsignedSmallInteger('crew_count');
            $table->string('fishing_method', 40)->index();
            $table->string('license_number', 80);
            $table->date('license_expiry_date')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->dateTime('submitted_at')->index();
            $table->timestamps();

            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('port_id')->references('id')->on('ports')->nullOnDelete();
            $table->foreign('boat_id')->references('id')->on('boats')->nullOnDelete();
            $table->foreign('captain_id')->references('id')->on('captains')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_submissions');
    }
};
