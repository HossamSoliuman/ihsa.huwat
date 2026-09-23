<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طلبات التسجيل من صفحة الهبوط: مالك قوارب أو دلال يطلب حسابًا، والمدير
 * العام يراجع الطلب فيعتمده أو يرفضه.
 *
 * الطلب ليس حسابًا بعد، فلا يُكتب في users: الجوال يبقى حرًّا حتى يُعتمد، ولا
 * يدخل صاحبه اللوحة ولا التطبيق قبل ذلك. كلمة المرور يختارها المتقدّم مع طلبه
 * وتُحفظ مجزّأة، فينتقل الحساب المعتمد بها كما هي ويدخل صاحبه فورًا.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->string('business_name')->nullable();
            $table->string('city')->nullable();
            // للمالك وحده: كم قاربًا يملك.
            $table->unsignedSmallInteger('boats_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('password');
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // الحساب الذي أنشأه الاعتماد.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
