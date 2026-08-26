<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قسم الخدمات والتراخيص — كتالوج الخدمات، وموظفو القسم وصلاحياتهم، وطلبات
 * الصيادين من التقديم إلى الاعتماد، وتذاكر الدعم الفني.
 *
 * الخدمة سطر في جدول لا قيمة في عمود: الموظف يُخوَّل بخدمات بعينها، والطلب
 * يُصنَّف بخدمة واحدة، والقسم المختص يُشتق من الخدمة — وكلها تحتاج مفتاحًا
 * حقيقيًا لا نصًا مكرّرًا. ولذلك جاء التخويل جدول ربط لا قائمة بفواصل.
 *
 * الميناء والمنطقة والقارب والموسم مفاتيح أجنبية كذلك: النطاق الجغرافي الذي
 * يُصفّى به عمل الموظف يُقرأ من الميناء إلى المحافظة إلى المنطقة، فلا يصح أن
 * يكون اسمًا حرًّا يختلف إملاؤه بين سطرين.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fisher_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->default('file-text');
            // القسم الذي يملك الخدمة — "تصحيح بيانات رحلة" وحدها تخصّ الإحصاء.
            $table->string('section')->default('الخدمات والتراخيص');
            $table->boolean('requires_season')->default(false);
            $table->boolean('issues_license')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('fisher_service_staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('job_number')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('role')->default('معالج');
            $table->string('section')->default('الخدمات والتراخيص');
            // نطاق جغرافي فارغ = تغطية على مستوى المملكة، فلا يبقى طلب بلا معالج.
            $table->foreignId('assigned_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('assigned_region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->boolean('can_create')->default(true);
            $table->boolean('can_process')->default(true);
            $table->boolean('can_approve')->default(false);
            $table->boolean('can_reject')->default(false);
            $table->boolean('can_assign')->default(false);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
         * الخدمات المخوّل بها الموظف. موظف بلا سطر هنا مخوّل بكل الخدمات —
         * الغياب يعني "الكل"، وهو ما يجعل إضافة خدمة جديدة لا تُعطّل أحدًا.
         */
        Schema::create('fisher_service_staff_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fisher_service_staff_id')->constrained('fisher_service_staff')->cascadeOnDelete();
            $table->foreignId('fisher_service_type_id')->constrained('fisher_service_types')->cascadeOnDelete();
            $table->unique(['fisher_service_staff_id', 'fisher_service_type_id'], 'staff_service_type_unique');
        });

        Schema::create('fisher_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('fisher_service_type_id')->constrained('fisher_service_types')->cascadeOnDelete();
            $table->foreignId('fisher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fisher_name');
            $table->string('national_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('blood_type')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality_type')->default('سعودي');
            $table->string('nationality')->nullable();
            $table->string('profession')->nullable();
            $table->string('employer')->nullable();
            $table->string('photo_url')->nullable();
            $table->foreignId('port_id')->nullable()->constrained()->nullOnDelete();
            $table->string('center')->nullable();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fishing_season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_number')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('جديدة');
            $table->string('priority')->default('عادية');
            $table->date('submitted_date');
            $table->date('processed_date')->nullable();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('fisher_service_staff')->nullOnDelete();
            $table->text('resolution')->nullable();
            // توقيع المسؤول المختص يُحفظ نصًا: هو إقرار شخص باسمه لحظة الإصدار،
            // ولا يتبدّل لو تغيّر سجل الموظف أو حُذف بعد سنوات.
            $table->string('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('new_license_number')->nullable();
            $table->date('new_license_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->string('category')->default('مشكلة تقنية');
            $table->string('priority')->default('عادية');
            $table->string('module')->nullable();
            $table->text('description');
            $table->string('submitted_by_name')->nullable();
            $table->string('submitted_by_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->dateTime('submitted_at');
            $table->string('status')->default('جديدة');
            $table->foreignId('assigned_staff_id')->nullable()->constrained('fisher_service_staff')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->text('resolution')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('fisher_service_requests');
        Schema::dropIfExists('fisher_service_staff_type');
        Schema::dropIfExists('fisher_service_staff');
        Schema::dropIfExists('fisher_service_types');
    }
};
