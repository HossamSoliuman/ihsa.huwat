<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المرحلة 4 — بوابة الدلال.
 *
 * مخزون الدلال لا يحتاج جدولًا: الإرسال من المرحلة 1 يكتب في دفتر المخزون
 * سطر "استلام من مالك" على الدلال بالرحلة والصنف، فالمتاح عنده = مجموع
 * حركاته كما عند المالك. ما يُضاف هنا:
 *
 * - `dalal_profiles`: ملف الدلال التجاري (الهوية، الموقع، السجل التجاري،
 *   الرقم الضريبي، الشركة والشعار، والدكة المنسوب إليها ورقمها).
 * - `dalal_worker_types` + `dalal_workers`: عمالة الدكة (النوع، الجنسية، العدد).
 * - `dalal_partnerships`: طلب المالك التعامل مع دلال بعمولة وأجور مقترحة،
 *   يقبله الدلال أو يرفضه — ومنه تُحسب العمولة على كل بيع من مصيد المالك.
 * - `dalal_payouts`: ما يدفعه الدلال للمالك من صافي مبيعات مصيده.
 * - `sale_items`: رحلة السطر ومالكه وعمولته وأجوره وصافي المالك — بيع الدلال
 *   الواحد قد يجمع مصيد أكثر من رحلة وأكثر من مالك بنِسب مختلفة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dalal_worker_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('dalal_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('id_number')->nullable();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('port_id')->nullable()->constrained()->nullOnDelete();
            $table->string('dakka_name')->nullable();
            $table->string('dakka_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('cr_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('dalal_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dalal_worker_type_id')->constrained()->cascadeOnDelete();
            $table->string('nationality')->nullable();
            $table->unsignedSmallInteger('count')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dalal_partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->decimal('wage_pct', 5, 2)->default(0);
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->text('response_note')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            // طلب واحد لكل مالك مع كل دلال: إعادة الطلب بعد الرفض تُحدّث السطر نفسه.
            $table->unique(['owner_id', 'dalal_id']);
        });

        Schema::create('dalal_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('trip_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->after('trip_id')->constrained('users')->nullOnDelete();
            $table->decimal('commission_amount', 12, 2)->default(0)->after('total');
            $table->decimal('wage_amount', 12, 2)->default(0)->after('commission_amount');
            $table->decimal('owner_net', 12, 2)->default(0)->after('wage_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_id');
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['commission_amount', 'wage_amount', 'owner_net']);
        });

        Schema::dropIfExists('dalal_payouts');
        Schema::dropIfExists('dalal_partnerships');
        Schema::dropIfExists('dalal_workers');
        Schema::dropIfExists('dalal_profiles');
        Schema::dropIfExists('dalal_worker_types');
    }
};
