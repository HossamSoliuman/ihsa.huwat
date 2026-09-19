<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أدوار تطبيق حوات (المالك، الكابتن، الطاقم، الموظف، العدّاد، الدلال، التاجر،
 * المدير العام) وربط حسابات المستخدمين بها.
 *
 * الدور سطر في جدول لا نصًا في عمود: القائمة الجانبية ووسيط الصلاحية وواجهة
 * التطبيق كلها تُفرَّع عليه، والتقارير تجمع عليه. أدوار الوزارة الثمانية تبقى
 * في user_permissions كما هي — هذه أدوار من يعمل في البحر والسوق لا في الوزارة.
 *
 * حساب التطبيق يدخل بجواله لا ببريده (شاشة الدخول في التطبيق جوال وكلمة مرور)،
 * فالبريد يصير اختياريًا والجوال فريدًا. وحساب الكابتن أو الطاقم أو الموظف يتبع
 * مالكًا (owner_id) لأنه هو من ينشئه ويُسند إليه قواربه ورحلاته.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->string('name');
            $table->string('name_en');
            $table->string('description')->nullable();
            // الدور له بوابة على /admin أم يعمل من التطبيق وحده.
            $table->boolean('has_portal')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->after('role_id')->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true)->after('owner_id');
            $table->string('locale', 5)->default('ar')->after('active');
            $table->string('avatar_path')->nullable()->after('locale');
            $table->string('fcm_token')->nullable()->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('fcm_token');
        });

        /*
         * رموز التحقق لاستعادة كلمة المرور بالجوال. الرمز يُخزَّن مُجزّأً لا صريحًا،
         * ويُستهلك مرة واحدة، وتُحسب محاولاته حتى لا يُخمَّن.
         */
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code_hash');
            $table->string('purpose', 40)->default('password_reset');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'active', 'locale', 'avatar_path', 'fcm_token', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });

        Schema::dropIfExists('roles');
    }
};
