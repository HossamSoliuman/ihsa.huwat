<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('information_submissions', function (Blueprint $table) {
            $table->string('status', 30)->default('submitted')->after('reference_no')->index();
            $table->unsignedInteger('reviewed_by')->nullable()->after('submitted_at');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('information_submission_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('submission_id');
            $table->string('event_type', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_id', 'created_at'], 'idx_information_events_submission');
            $table->foreign('submission_id')->references('id')->on('information_submissions')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('information_submissions')->orderBy('id')->chunkById(200, function ($submissions): void {
            $events = [];

            foreach ($submissions as $submission) {
                $events[] = [
                    'submission_id' => $submission->id,
                    'event_type' => 'submitted',
                    'to_status' => 'submitted',
                    'actor_user_id' => $submission->submitted_by,
                    'created_at' => $submission->submitted_at ?? $submission->created_at ?? now(),
                ];
            }

            DB::table('information_submission_events')->insert($events);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_submission_events');

        Schema::table('information_submissions', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
