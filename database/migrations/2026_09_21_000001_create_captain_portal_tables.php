<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المرحلة 2 — بوابة الكابتن: إشعارات التطبيق.
 *
 * الرحلة نفسها لا تحتاج أعمدة جديدة — دورتها كاملة منذ المرحلة 1 — لكن
 * الكابتن يُبلَّغ بما يخصّه (رحلة جديدة بانتظارك، أُلغيت، اكتملت…) والمالك
 * بما يفعله الكابتن. الإشعار سطر في جدول يُقرأ من الويب والتطبيق، ويُدفع
 * إلى الجوال عبر Firebase حين يُفعَّل التكامل.
 *
 * نوع الإشعار قائمة مرجعية بجدولها (لا عمود enum): التقارير تجمع عليه
 * والقائمة تُحرَّر من الإدارة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->string('icon', 40)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('body', 500);
            // بيانات يفتح بها التطبيق الشاشة المناسبة (رقم الرحلة، الحالة…).
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('notification_types');
    }
};
