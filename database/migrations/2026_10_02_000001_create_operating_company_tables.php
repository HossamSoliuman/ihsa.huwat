<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شركات التشغيل والعدّادون الذين توظّفهم.
 *
 * الشركة يُنشئها المدير العام ويُسند إليها موانئ (ميناء واحد لشركة واحدة)،
 * وتفتح في موانئها جولات توظيف بمقاعد ومدة، فيتقدّم العدّاد بنفسه ويوثّق
 * جواله، وتعتمده الشركة فيُنشأ حسابه وسجلّ موظف الإحصاء الذي يعمل به.
 *
 * العدّاد يبقى صفًّا في statistics_officers كما في المرحلة 3 — الطابور يُحسب
 * على ميناء ذلك السجلّ — ويُضاف إليه شركته؛ عدّادو الوزارة شركتهم null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operating_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('commercial_register', 20)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('operating_company_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operating_company_id')->constrained()->cascadeOnDelete();
            // فريد: الميناء تشغّله شركة واحدة في الوقت الواحد.
            $table->foreignId('port_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('started_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hiring_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operating_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('port_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('seats');
            $table->date('opens_at');
            $table->date('closes_at');
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('counter_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('operating_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('port_id')->constrained()->restrictOnDelete();
            // مفتاح المتقدّم لمتابعة طلبه قبل أن يكون له حساب — في رابط الويب وفي التطبيق.
            $table->string('token', 64)->unique();
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('national_id', 10);
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('qualification')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->text('notes')->nullable();
            $table->string('password');
            // الطلب لا يصل الشركة قبل توثيق الجوال برمز التحقق.
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // الحساب الذي أنشأه الاعتماد.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::table('statistics_officers', function (Blueprint $table) {
            $table->foreignId('operating_company_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('counter_application_id')->nullable()->after('operating_company_id')->constrained()->nullOnDelete();
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable()->after('suspended_by');
        });

        Schema::create('counter_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('statistics_officer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_port_id')->constrained('ports')->restrictOnDelete();
            $table->foreignId('to_port_id')->constrained('ports')->restrictOnDelete();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // لحسابات موظفي الشركة (دور company) وحدها.
            $table->foreignId('operating_company_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operating_company_id');
        });

        Schema::dropIfExists('counter_transfers');

        Schema::table('statistics_officers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropConstrainedForeignId('counter_application_id');
            $table->dropConstrainedForeignId('operating_company_id');
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });

        Schema::dropIfExists('counter_applications');
        Schema::dropIfExists('hiring_rounds');
        Schema::dropIfExists('operating_company_ports');
        Schema::dropIfExists('operating_companies');
    }
};
