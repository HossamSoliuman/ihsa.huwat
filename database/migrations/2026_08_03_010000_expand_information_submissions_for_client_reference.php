<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('information_submissions', function (Blueprint $table) {
            $table->string('owner_nationality', 80)->nullable()->after('owner_national_id');
            $table->date('owner_birth_date')->nullable()->after('owner_nationality');
            $table->string('owner_email', 190)->nullable()->after('owner_birth_date');
            $table->string('owner_region', 150)->nullable()->after('owner_phone');
            $table->string('owner_governorate', 150)->nullable()->after('owner_region');
            $table->string('owner_city', 150)->nullable()->after('owner_governorate');
            $table->string('owner_address', 250)->nullable()->after('owner_city');
            $table->date('license_issue_date')->nullable()->after('license_number');
            $table->json('boat_data')->nullable()->after('license_expiry_date');
            $table->json('captain_data')->nullable()->after('boat_data');
            $table->json('crew_members')->nullable()->after('captain_data');
            $table->json('fishing_tools')->nullable()->after('crew_members');
            $table->json('document_paths')->nullable()->after('document_path');
            $table->string('captain_photo_path', 500)->nullable()->after('document_paths');
            $table->dateTime('consented_at')->nullable()->after('captain_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('information_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'owner_nationality',
                'owner_birth_date',
                'owner_email',
                'owner_region',
                'owner_governorate',
                'owner_city',
                'owner_address',
                'license_issue_date',
                'boat_data',
                'captain_data',
                'crew_members',
                'fishing_tools',
                'document_paths',
                'captain_photo_path',
                'consented_at',
            ]);
        });
    }
};
