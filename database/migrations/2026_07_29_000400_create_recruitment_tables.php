<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference_no', 30)->unique();
            $table->string('title_ar', 190);
            $table->string('department', 190)->nullable();
            $table->text('summary');
            $table->text('description');
            $table->text('responsibilities')->nullable();
            $table->text('requirements');
            $table->enum('employment_type', ['full_time', 'part_time', 'temporary', 'contract'])->default('full_time');
            $table->unsignedSmallInteger('vacancies')->default(1);
            $table->unsignedInteger('port_id')->nullable();
            $table->string('city', 120)->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->date('application_deadline')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index(['status', 'application_deadline', 'published_at'], 'idx_employment_jobs_public');
            $table->index('port_id', 'idx_employment_jobs_port');
            $table->foreign('port_id')->references('id')->on('ports')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('employment_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('job_id');
            $table->string('reference_no', 40)->unique();
            $table->enum('status', ['submitted', 'under_review', 'shortlisted', 'interview', 'accepted', 'rejected', 'account_created', 'withdrawn'])->default('submitted');
            $table->string('full_name', 190);
            $table->string('nationality', 100);
            $table->enum('identity_type', ['national_id', 'residency', 'passport']);
            $table->string('identity_number', 50);
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed']);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->string('mobile', 30);
            $table->string('phone', 30)->nullable();
            $table->string('email', 190);
            $table->string('city', 120);
            $table->text('address');
            $table->unsignedInteger('preferred_port_id')->nullable();
            $table->enum('work_type', ['full_time', 'part_time', 'temporary', 'contract'])->default('full_time');
            $table->enum('source', ['website', 'social_media', 'referral', 'job_fair', 'other'])->default('website');
            $table->enum('education_level', ['high_school', 'diploma', 'bachelor', 'master', 'doctorate', 'other']);
            $table->string('specialization', 190);
            $table->string('institution', 190);
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->decimal('experience_years', 4, 1)->default(0);
            $table->string('current_employer', 190)->nullable();
            $table->string('current_job_title', 190)->nullable();
            $table->text('professional_summary')->nullable();
            $table->text('skills');
            $table->date('availability_date')->nullable();
            $table->text('cover_letter')->nullable();
            $table->boolean('consent')->default(false);
            $table->text('admin_note')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->unsignedInteger('employee_user_id')->nullable()->unique();
            $table->dateTime('submitted_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['job_id', 'identity_number'], 'uniq_employment_job_identity');
            $table->index(['status', 'submitted_at'], 'idx_employment_applications_queue');
            $table->index(['job_id', 'status'], 'idx_employment_applications_job_status');
            $table->index('email', 'idx_employment_applications_email');
            $table->foreign('job_id')->references('id')->on('employment_jobs');
            $table->foreign('preferred_port_id')->references('id')->on('ports')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('employment_application_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('application_id');
            $table->enum('attachment_type', ['cv', 'identity', 'certificate', 'other']);
            $table->string('original_name');
            $table->string('stored_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'attachment_type'], 'idx_employment_attachments_application');
            $table->foreign('application_id')->references('id')->on('employment_applications')->cascadeOnDelete();
        });

        Schema::create('employment_application_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('application_id');
            $table->string('event_type', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at'], 'idx_employment_events_application');
            $table->foreign('application_id')->references('id')->on('employment_applications')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('employment_application_id', 'fk_employees_employment_application')->references('id')->on('employment_applications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign('fk_employees_employment_application');
        });
        Schema::dropIfExists('employment_application_events');
        Schema::dropIfExists('employment_application_attachments');
        Schema::dropIfExists('employment_applications');
        Schema::dropIfExists('employment_jobs');
    }
};
