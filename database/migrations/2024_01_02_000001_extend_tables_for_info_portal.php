<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بوابة المعلومات (مركز إدارة النظام) تحرّر نفس جداول لوحة الوزارة، لكنها تعرض
 * حقولًا مرجعية إضافية لا تحتاجها لوحة العرض. هذه الهجرة تضيف تلك الحقول فقط —
 * البنية العلائقية (region_id / governorate_id / port_id) تبقى كما هي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governorates', function (Blueprint $table) {
            $table->boolean('coastal')->default(true)->after('code');
        });

        Schema::table('fishing_sites', function (Blueprint $table) {
            $table->decimal('depth_m', 8, 2)->nullable()->after('lng');
            $table->unsignedInteger('trips_count')->default(0)->after('depth_m');
            $table->unsignedInteger('boats_count')->default(0)->after('trips_count');
            $table->decimal('avg_catch_per_trip', 10, 2)->default(0)->after('catch_kg');
        });

        Schema::table('boats', function (Blueprint $table) {
            $table->unsignedInteger('crew_capacity')->default(0)->after('crew_count');
            $table->string('license_type')->nullable()->after('license_status');
            $table->string('license_number')->nullable()->after('license_type');
            $table->date('license_expiry')->nullable()->after('license_number');
            $table->unsignedInteger('trips_count')->default(0)->after('license_expiry');
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->foreignId('boat_id')->nullable()->after('port_id')->constrained()->nullOnDelete();
            $table->date('license_expiry')->nullable()->after('license_status');
            $table->unsignedInteger('experience_years')->default(0)->after('license_expiry');
            $table->unsignedInteger('trips_count')->default(0)->after('experience_years');
        });

        Schema::table('statistics_officers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('employee_number');
            $table->string('phone')->nullable()->after('email');
        });

        Schema::table('gear_types', function (Blueprint $table) {
            $table->string('isscfg_code')->nullable()->after('category');
            $table->unsignedInteger('min_mesh_size_mm')->nullable()->after('isscfg_code');
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('fishing_seasons', function (Blueprint $table) {
            $table->string('season_type')->default('موسم صيد')->after('region');
        });

        Schema::table('season_licenses', function (Blueprint $table) {
            $table->foreignId('port_id')->nullable()->after('boat_id')->constrained()->nullOnDelete();
            $table->string('species')->nullable()->after('holder_name');
            $table->string('captain')->nullable()->after('fisher_name');
        });

        Schema::table('markets', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('market_auctions', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('auction_date');
            $table->decimal('min_price_per_kg', 8, 2)->nullable()->after('quantity_sold_kg');
            $table->decimal('max_price_per_kg', 8, 2)->nullable()->after('min_price_per_kg');
            $table->string('source_port')->nullable()->after('buyer_type');
            $table->text('notes')->nullable()->after('source_port');
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('user_email');
            $table->string('scope_level')->default('المملكة')->after('role');
            $table->boolean('can_approve')->default(false)->after('port');
            $table->boolean('can_export')->default(false)->after('can_approve');
            $table->text('notes')->nullable()->after('active');
        });

        Schema::table('ui_translations', function (Blueprint $table) {
            $table->string('status')->default('مترجم')->after('context');
        });

        Schema::table('data_catalog_assets', function (Blueprint $table) {
            $table->string('steward_email')->nullable()->after('owner_email');
            $table->boolean('contains_pii')->default(false)->after('sensitivity');
            $table->string('refresh_mode')->nullable()->after('contains_pii');
            $table->string('retention_note')->nullable()->after('refresh_mode');
            $table->text('purpose')->nullable()->after('retention_note');
            $table->boolean('quality_controlled')->default(false)->after('purpose');
        });

        Schema::table('data_lineage_edges', function (Blueprint $table) {
            $table->string('refresh_mode')->nullable()->after('transform');
            $table->boolean('active')->default(true)->after('refresh_mode');
            $table->text('notes')->nullable()->after('active');
        });

        Schema::table('business_glossary_terms', function (Blueprint $table) {
            $table->text('definition_en')->nullable()->after('definition');
            $table->string('owner_email')->nullable()->after('domain');
            $table->string('related_entities')->nullable()->after('owner_email');
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('kpi_registries', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name_ar');
            $table->string('domain')->nullable()->after('name_en');
            $table->string('unit')->nullable()->after('domain');
            $table->string('source_entities')->nullable()->after('formula');
            $table->string('refresh_mode')->nullable()->after('owner');
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('data_quality_issues', function (Blueprint $table) {
            $table->string('fingerprint')->nullable()->after('id');
            $table->string('run_id')->nullable()->after('fingerprint');
            $table->string('record_id')->nullable()->after('entity_name');
            $table->string('field_name')->nullable()->after('record_label');
            $table->text('resolution_note')->nullable()->after('due_date');
        });

        Schema::table('fao_standard_mappings', function (Blueprint $table) {
            $table->string('local_id')->nullable()->after('local_entity_type');
            $table->string('local_name_en')->nullable()->after('local_name_ar');
            $table->string('scientific_name')->nullable()->after('fao_name');
            $table->string('source_url', 500)->nullable()->after('scientific_name');
            $table->date('valid_from')->nullable()->after('verification_status');
            $table->date('valid_to')->nullable()->after('valid_from');
            $table->text('notes')->nullable()->after('valid_to');
        });
    }

    public function down(): void
    {
        Schema::table('fao_standard_mappings', function (Blueprint $table) {
            $table->dropColumn(['local_id', 'local_name_en', 'scientific_name', 'source_url', 'valid_from', 'valid_to', 'notes']);
        });

        Schema::table('data_quality_issues', function (Blueprint $table) {
            $table->dropColumn(['fingerprint', 'run_id', 'record_id', 'field_name', 'resolution_note']);
        });

        Schema::table('kpi_registries', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'domain', 'unit', 'source_entities', 'refresh_mode', 'notes']);
        });

        Schema::table('business_glossary_terms', function (Blueprint $table) {
            $table->dropColumn(['definition_en', 'owner_email', 'related_entities', 'notes']);
        });

        Schema::table('data_lineage_edges', function (Blueprint $table) {
            $table->dropColumn(['refresh_mode', 'active', 'notes']);
        });

        Schema::table('data_catalog_assets', function (Blueprint $table) {
            $table->dropColumn(['steward_email', 'contains_pii', 'refresh_mode', 'retention_note', 'purpose', 'quality_controlled']);
        });

        Schema::table('ui_translations', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'scope_level', 'can_approve', 'can_export', 'notes']);
        });

        Schema::table('market_auctions', function (Blueprint $table) {
            $table->dropColumn(['grade', 'min_price_per_kg', 'max_price_per_kg', 'source_port', 'notes']);
        });

        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('season_licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('port_id');
            $table->dropColumn(['species', 'captain']);
        });

        Schema::table('fishing_seasons', function (Blueprint $table) {
            $table->dropColumn('season_type');
        });

        Schema::table('gear_types', function (Blueprint $table) {
            $table->dropColumn(['isscfg_code', 'min_mesh_size_mm', 'notes']);
        });

        Schema::table('statistics_officers', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('boat_id');
            $table->dropColumn(['license_expiry', 'experience_years', 'trips_count']);
        });

        Schema::table('boats', function (Blueprint $table) {
            $table->dropColumn(['crew_capacity', 'license_type', 'license_number', 'license_expiry', 'trips_count']);
        });

        Schema::table('fishing_sites', function (Blueprint $table) {
            $table->dropColumn(['depth_m', 'trips_count', 'boats_count', 'avg_catch_per_trip']);
        });

        Schema::table('governorates', function (Blueprint $table) {
            $table->dropColumn('coastal');
        });
    }
};
