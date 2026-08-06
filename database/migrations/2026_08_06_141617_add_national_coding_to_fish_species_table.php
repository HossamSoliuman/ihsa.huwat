<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Species used to be a bare Arabic name. The catch coding sheet gives every one of
     * them a national code, a scientific and English name, and the two local names the
     * Gulf and the Red Sea use — all of which the statistics side needs to report on.
     */
    public function up(): void
    {
        Schema::table('fish_species', function (Blueprint $table) {
            $table->unsignedInteger('fish_family_id')->nullable()->after('id');
            $table->unsignedSmallInteger('code')->nullable()->unique()->after('fish_family_id');
            $table->string('scientific_name', 150)->nullable()->after('name_ar');
            $table->string('english_name', 190)->nullable()->after('scientific_name');
            $table->string('local_name_gulf', 190)->nullable()->after('english_name');
            $table->string('local_name_red_sea', 190)->nullable()->after('local_name_gulf');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /** Local names repeat across the sheet — كنعد alone lands on several codes — so the code carries identity now. */
        Schema::table('fish_species', function (Blueprint $table) {
            $table->dropUnique(['name_ar']);
        });

        Schema::table('fish_species', function (Blueprint $table) {
            $table->foreign('fish_family_id')->references('id')->on('fish_families')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fish_species', function (Blueprint $table) {
            $table->dropForeign(['fish_family_id']);
        });

        Schema::table('fish_species', function (Blueprint $table) {
            $table->dropColumn([
                'fish_family_id',
                'code',
                'scientific_name',
                'english_name',
                'local_name_gulf',
                'local_name_red_sea',
                'sort_order',
                'is_active',
                'created_at',
                'updated_at',
            ]);
        });

        Schema::table('fish_species', function (Blueprint $table) {
            $table->unique('name_ar');
        });
    }
};
