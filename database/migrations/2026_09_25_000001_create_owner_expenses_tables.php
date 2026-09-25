<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O1 — مصروفات المالك (منقولة من وحدة المصروفات في hispa ومبنية على
 * أعراف بوابة المالك).
 *
 * - `expense_groups`: المجموعة الكبرى (عامة، تشغيلية، حكومية، صيانة) —
 *   ما كان في hispa عمود `type` على الفئة الأم.
 * - `expense_categories`: الفئة داخل مجموعتها (وقود، ثلج، قطع غيار…).
 * - `expenses`: سند المصروف — المبلغ قبل الخصم، الخصم (مبلغًا أو نسبة)،
 *   الضريبة بنسبتها ومبلغها، الإجمالي والمدفوع منه. القارب والرحلة مفتاحان
 *   مباشران لأن التقارير (ربحية القارب والرحلة، إغلاق الشهر) تجمع عليهما،
 *   و`source` يشير إلى السجل الذي ولّد المصروف تلقائيًا (صيانة مكتملة الآن،
 *   ومعدات وأصول ورواتب في المراحل التالية).
 * - `boat_maintenances.actual_cost`: التكلفة الفعلية عند اكتمال الصيانة —
 *   هي ما يُرحَّل مصروفًا (وإلا فالتكلفة المتوقعة).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_group_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('source');
            $table->date('date');
            $table->string('description')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'date']);
        });

        Schema::table('boat_maintenances', function (Blueprint $table) {
            $table->decimal('actual_cost', 12, 2)->nullable()->after('estimated_cost');
        });
    }

    public function down(): void
    {
        Schema::table('boat_maintenances', function (Blueprint $table) {
            $table->dropColumn('actual_cost');
        });

        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('expense_groups');
    }
};
