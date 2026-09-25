<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O3 — مال الطاقم (منقول من hispa: مسيرات الرواتب، الحصص، السلف).
 *
 * - `pay_types`: نوع أجر الفرد — نسبة من أرباح القارب أو راتب ثابت.
 * - `fishers`: إعداد أجر الفرد (النوع، الراتب الثابت، الأسهم، النسبة الخاصة).
 * - `boats.owner_share_percent`: نصيب المالك من صافي ربح القارب، والباقي للطاقم.
 * - `payrolls` + `payroll_lines`: مسير شهر لقارب — أرقام الشهر (الإيراد،
 *   المصروفات، الإهلاك، الصافي، نصيب الطاقم) وسطر لكل فرد بأجره وزيادته
 *   وخصمه وسلفه المخصومة وصافيه وسداده. السطر المسدَّد يُجمَّد.
 * - `crew_advances`: السلف النقدية للفرد، تُخصم من أول مسير غير مسدَّد
 *   لشهرها أو بعده.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->foreignId('pay_type_id')->nullable()->after('fisher_role_id')->constrained()->nullOnDelete();
            $table->decimal('fixed_salary', 12, 2)->nullable()->after('pay_type_id');
            $table->decimal('profit_shares', 5, 2)->default(1)->after('fixed_salary');
            $table->decimal('custom_share_percent', 5, 2)->nullable()->after('profit_shares');
        });

        Schema::table('boats', function (Blueprint $table) {
            $table->decimal('owner_share_percent', 5, 2)->default(50);
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_number')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('boat_name');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('expenses', 12, 2)->default(0);
            $table->decimal('depreciation', 12, 2)->default(0);
            $table->decimal('depreciation_charged', 12, 2)->default(0);
            $table->decimal('depreciation_deferred', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('owner_share_percent', 5, 2)->default(50);
            $table->decimal('owner_share', 12, 2)->default(0);
            $table->decimal('crew_pool', 12, 2)->default(0);
            $table->foreignId('payment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'boat_id', 'year', 'month']);
        });

        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fisher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_name');
            $table->boolean('is_captain')->default(false);
            $table->foreignId('pay_type_id')->constrained()->restrictOnDelete();
            $table->decimal('fixed_salary', 12, 2)->nullable();
            $table->decimal('profit_shares', 5, 2)->default(1);
            $table->decimal('custom_share_percent', 5, 2)->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->decimal('advances', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'fisher_id']);
        });

        Schema::create('crew_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fisher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_name');
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'fisher_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_advances');
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payrolls');

        Schema::table('boats', function (Blueprint $table) {
            $table->dropColumn('owner_share_percent');
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pay_type_id');
            $table->dropColumn(['fixed_salary', 'profit_shares', 'custom_share_percent']);
        });

        Schema::dropIfExists('pay_types');
    }
};
