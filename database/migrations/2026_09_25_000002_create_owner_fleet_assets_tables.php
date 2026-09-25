<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O2 — أصول الأسطول (منقولة من hispa: المعدات، الأصول والإهلاك، الفحوصات،
 * الوثائق، فحص الطاقم).
 *
 * - `fishing_equipment` + `fishing_equipment_season`: معدات المالك (نوع
 *   الأداة من `gear_types` الوزارية) والمواسم الوزارية التي تُستخدم فيها.
 *   تكلفة شرائها تُرحَّل مصروفًا (`expenses.source`) كما الصيانة.
 * - `asset_types` + `assets`: أصول المالك بإهلاك القسط الثابت شهريًا —
 *   (التكلفة − الخردة) ÷ العمر ÷ 12 من شهر الشراء حتى نهاية العمر أو شهر
 *   التخلّص. الإهلاك يُحسب ولا يُخزَّن (إغلاق الشهر O4 يثبّته)، والأصل لا
 *   يُرحَّل مصروفًا: تكلفته تُحمَّل عبر الإهلاك.
 * - `boat_inspections`: فحوصات القارب؛ آخرها يحدّث `boats.next_inspection_date`.
 * - `document_types` + `fleet_documents`: وثائق القارب أو فرد الطاقم (رقم،
 *   إصدار، انتهاء، مرفق) — منها تنبيهات الانتهاء وامتثال الطاقم.
 */
return new class extends Migration
{
    private const LOOKUPS = ['asset_types', 'document_types'];

    public function up(): void
    {
        foreach (self::LOOKUPS as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('name_en')->nullable();
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::create('fishing_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gear_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->date('purchase_date')->nullable();
            $table->string('condition')->default('جيدة');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fishing_equipment_season', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fishing_equipment_id')->constrained('fishing_equipment')->cascadeOnDelete();
            $table->foreignId('fishing_season_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['fishing_equipment_id', 'fishing_season_id'], 'equipment_season_unique');
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('salvage_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('useful_life_years');
            $table->string('status')->default('نشط');
            $table->date('disposed_at')->nullable();
            $table->decimal('disposal_value', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
        });

        Schema::create('boat_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boat_id')->constrained()->cascadeOnDelete();
            $table->date('inspection_date');
            $table->date('next_due_date')->nullable();
            $table->string('inspector')->nullable();
            $table->string('result')->default('مطابق');
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['boat_id', 'inspection_date']);
        });

        Schema::create('fleet_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('documentable');
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_documents');
        Schema::dropIfExists('boat_inspections');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('fishing_equipment_season');
        Schema::dropIfExists('fishing_equipment');

        foreach (array_reverse(self::LOOKUPS) as $lookup) {
            Schema::dropIfExists($lookup);
        }
    }
};
