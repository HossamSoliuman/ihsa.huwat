<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قسم الإدارة الفرعية — الهيكل التنظيمي وشاغلوه، والمهام الإدارية، والتنبيهات
 * الموجّهة للموظفين، وتفضيلات الإشعارات.
 *
 * المنصب يشير إلى منصب أعلى بالمعرّف لا بالاسم، فيبقى الهيكل شجرة واحدة صحيحة
 * عند تعديل مسمّى. وجدول الإنذارات يكتسب هنا حقول الإسناد والإغلاق: التنبيه لا
 * يُغلق قبل أن يكون له مسؤول، فيلزم تسجيل من أُسند إليه ومتى.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('level')->default('موظف مشرف');
            $table->foreignId('parent_id')->nullable()->constrained('org_positions')->nullOnDelete();
            $table->text('authorities')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('linked_role')->default('user');
            $table->string('scope_level')->default('kingdom');
            $table->string('reports_to')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('org_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_position_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('job_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('rank')->default('الرتبة الثالثة');
            $table->string('status')->default('نشط');
            $table->date('start_date')->nullable();
            // صلاحيات الإجراء — تُقيّد من يجوز إسناد المهمة الإدارية إليه.
            $table->boolean('can_create')->default(false);
            $table->boolean('can_process')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_reject')->default(false);
            $table->boolean('can_assign')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('org_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('org_staff')->nullOnDelete();
            $table->string('required_permission')->default('أي صلاحية');
            $table->string('task_type')->default('متابعة');
            $table->string('section')->default('الإدارة الفرعية');
            $table->string('priority')->default('عادية');
            $table->string('status')->default('مجدولة');
            $table->date('start_date')->nullable();
            $table->date('due_date');
            $table->dateTime('completed_at')->nullable();
            $table->string('completed_by')->nullable();
            $table->string('recurrence')->default('بدون');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->foreignId('org_staff_id')->nullable()->constrained('org_staff')->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('request_number')->nullable();
            $table->string('notification_type')->default('طلب جديد');
            $table->string('priority')->default('عادية');
            $table->boolean('read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });

        /*
         * قنوات التنبيه القابلة للتفعيل في صفحة الإعدادات — سطر لكل قناة، لا
         * جدول مفاتيح وقيم عام: القناة كيان له اسمه ووصفه وترتيب عرضه.
         */
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->string('site')->nullable()->after('species');
            $table->string('assigned_to')->nullable()->after('status');
            $table->dateTime('assigned_at')->nullable()->after('assigned_to');
            $table->text('resolution_note')->nullable()->after('assigned_at');
            $table->dateTime('closed_at')->nullable()->after('resolution_note');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn(['site', 'assigned_to', 'assigned_at', 'resolution_note', 'closed_at']);
        });

        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('staff_notifications');
        Schema::dropIfExists('admin_tasks');
        Schema::dropIfExists('org_staff');
        Schema::dropIfExists('org_positions');
    }
};
