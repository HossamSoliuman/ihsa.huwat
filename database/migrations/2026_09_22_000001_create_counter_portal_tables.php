<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المرحلة 3 — بوابة العدّاد: ربط موظف الإحصاء بحساب تطبيق.
 *
 * العدّاد موظف الإحصاء في الوزارة (`statistics_officers`) لا موظفٌ عند المالك
 * (القرار 2 في §8)، فسجلّه موجود منذ الأسطول الأول ومعه ميناؤه ورديّته وعدد
 * رحلاته. ما ينقصه حساب يدخل به التطبيق ولوحته — سطر `user_id` واحد يصل
 * السجلّ بالحساب، فيُعرف ميناء العدّاد ويُحسب طابوره ويُعرض في ملفه الشخصي
 * كما يُعرض ميناء الكابتن من سجلّ الصياد.
 *
 * الرحلة لا تحتاج أعمدة جديدة: `counter_id` و`received_at` و`counted_at`
 * كلها من المرحلة 1، وسطور المصيد تحمل `counted_kg` و`counter_notes`
 * و`verified` و`corrected_by` منذ ذلك الحين.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statistics_officers', function (Blueprint $table) {
            // فريد: حساب واحد لكل سجلّ موظف، وسجلّ واحد لكل حساب عدّاد.
            $table->foreignId('user_id')->nullable()->unique()->after('port_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('statistics_officers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
