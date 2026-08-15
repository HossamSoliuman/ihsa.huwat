<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_catalog_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_key');
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->string('asset_type');
            $table->string('domain');
            $table->string('system');
            $table->boolean('source_of_truth')->default(false);
            $table->string('owner_email')->nullable();
            $table->string('steward_email')->nullable();
            $table->string('sensitivity')->default('داخلي');
            $table->boolean('contains_pii')->default(false);
            $table->string('refresh_mode')->default('عند الطلب');
            $table->string('retention_note')->nullable();
            $table->text('purpose')->nullable();
            $table->boolean('quality_controlled')->default(true);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('data_lineage_edges', function (Blueprint $table) {
            $table->id();
            $table->string('source_asset_key');
            $table->string('target_asset_key');
            $table->string('transformation')->nullable();
            $table->string('refresh_mode')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('business_glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term_ar');
            $table->string('term_en')->nullable();
            $table->string('domain')->nullable();
            $table->text('definition_ar')->nullable();
            $table->text('definition_en')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('related_entities')->nullable();
            $table->string('status')->default('معتمد');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('kpi_registries', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_key');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('domain')->nullable();
            $table->string('unit')->nullable();
            $table->text('formula')->nullable();
            $table->string('source_entities')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('refresh_mode')->default('يومي');
            $table->string('certification_status')->default('مسودة');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('data_quality_issues', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint');
            $table->string('run_id')->nullable();
            $table->string('category');
            $table->string('severity')->default('warning');
            $table->string('entity_name');
            $table->string('record_id')->nullable();
            $table->string('record_label')->nullable();
            $table->string('field_name')->nullable();
            $table->text('issue_message');
            $table->string('status')->default('مفتوحة');
            $table->string('priority')->default('متوسطة');
            $table->string('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });

        Schema::create('fao_standard_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('local_entity_type');
            $table->string('local_id')->nullable();
            $table->string('local_name_ar')->nullable();
            $table->string('local_name_en')->nullable();
            $table->string('fao_system');
            $table->string('fao_code')->nullable();
            $table->string('fao_name')->nullable();
            $table->string('scientific_name')->nullable();
            $table->string('source_url')->nullable();
            $table->string('verification_status')->default('غير محقق');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fao_standard_mappings');
        Schema::dropIfExists('data_quality_issues');
        Schema::dropIfExists('kpi_registries');
        Schema::dropIfExists('business_glossary_terms');
        Schema::dropIfExists('data_lineage_edges');
        Schema::dropIfExists('data_catalog_assets');
    }
};