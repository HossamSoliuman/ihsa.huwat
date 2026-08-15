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
            $table->string('asset_key')->unique();
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->string('asset_type');
            $table->string('domain');
            $table->string('system');
            $table->boolean('source_of_truth')->default(false);
            $table->string('owner_email')->nullable();
            $table->string('sensitivity')->default('داخلي');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('data_lineage_edges', function (Blueprint $table) {
            $table->id();
            $table->string('source_asset');
            $table->string('target_asset');
            $table->string('transform')->nullable();
            $table->timestamps();
        });

        Schema::create('business_glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term_ar')->unique();
            $table->string('term_en')->nullable();
            $table->text('definition');
            $table->string('domain')->nullable();
            $table->string('status')->default('معتمد');
            $table->timestamps();
        });

        Schema::create('kpi_registries', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_key')->unique();
            $table->string('name_ar');
            $table->text('formula')->nullable();
            $table->string('owner')->nullable();
            $table->string('status')->default('معتمد');
            $table->timestamps();
        });

        Schema::create('data_quality_issues', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('severity')->default('warning');
            $table->string('entity_name');
            $table->string('record_label')->nullable();
            $table->text('issue_message');
            $table->string('status')->default('مفتوحة');
            $table->string('priority')->default('متوسطة');
            $table->string('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('fao_standard_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('local_entity_type');
            $table->string('local_name_ar')->nullable();
            $table->string('fao_system');
            $table->string('fao_code')->nullable();
            $table->string('fao_name')->nullable();
            $table->string('verification_status')->default('غير محقق');
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