<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O4 — إغلاق الشهر (منقول من hispa: month_closings + month_closing_dues).
 *
 * - `month_closings`: إغلاق شهر للمالك كله — لقطة مجمَّدة من أرقام الشهر:
 *   مجموع القوارب (الإيراد، المصروفات، الإهلاك ومحمَّله ومؤجَّله، الصافي،
 *   نصيب المالك ونصيب الطاقم) + ما لا قارب له (المصروفات العامة وإهلاك
 *   الأصول غير المربوطة بقارب) + صافي المالك. وجود الصف = الشهر مُغلق.
 * - `month_closing_boats`: سطر لكل قارب له نشاط في الشهر بأرقام `CrewPool`
 *   ومؤجَّل الإهلاك الداخل من الشهر السابق والخارج إلى التالي، ومسيره.
 *   مستحقات الطاقم هي سطور المسير (O3) — لا نسخة ثانية منها هنا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('month_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('boat_expenses', 12, 2)->default(0);
            $table->decimal('depreciation', 12, 2)->default(0);
            $table->decimal('depreciation_charged', 12, 2)->default(0);
            $table->decimal('depreciation_deferred', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('owner_share', 12, 2)->default(0);
            $table->decimal('crew_pool', 12, 2)->default(0);
            $table->decimal('general_expenses', 12, 2)->default(0);
            $table->decimal('general_depreciation', 12, 2)->default(0);
            $table->json('general_assets')->nullable();
            $table->decimal('owner_net', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at');
            $table->timestamps();

            $table->unique(['owner_id', 'year', 'month']);
        });

        Schema::create('month_closing_boats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('month_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('boat_name');
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('expenses', 12, 2)->default(0);
            $table->decimal('depreciation_own', 12, 2)->default(0);
            $table->decimal('depreciation_brought_forward', 12, 2)->default(0);
            $table->decimal('depreciation', 12, 2)->default(0);
            $table->decimal('depreciation_charged', 12, 2)->default(0);
            $table->decimal('depreciation_deferred', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('owner_share_percent', 5, 2)->default(50);
            $table->decimal('owner_share', 12, 2)->default(0);
            $table->decimal('crew_pool', 12, 2)->default(0);
            $table->json('assets')->nullable();
            $table->timestamps();

            $table->unique(['month_closing_id', 'boat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_closing_boats');
        Schema::dropIfExists('month_closings');
    }
};
