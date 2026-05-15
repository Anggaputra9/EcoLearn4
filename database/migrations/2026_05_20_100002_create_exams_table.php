<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('duration_minutes')->default(60);   // 0 = tanpa batas
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');

            // Anti-cheat & pengaturan
            $table->boolean('prevent_tab_switch')->default(true);
            $table->unsignedTinyInteger('max_tab_switch')->default(0);       // 0 = langsung gugur
            $table->boolean('prevent_copy_paste')->default(true);
            $table->boolean('prevent_right_click')->default(true);
            $table->boolean('fullscreen_required')->default(false);
            $table->boolean('shuffle_questions')->default(false);

            // Mode koreksi
            $table->enum('grading_mode', ['auto_ai', 'manual', 'hybrid'])->default('auto_ai');

            // Visibility
            $table->boolean('show_result_after_submit')->default(true);
            $table->boolean('show_leaderboard')->default(false);
            $table->boolean('allow_review_answer')->default(true);

            $table->timestamps();
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'disqualified', 'expired'])->default('in_progress');

            $table->unsignedSmallInteger('tab_switch_count')->default(0);
            $table->text('cheat_log')->nullable();        // JSON event log

            $table->unsignedSmallInteger('total_score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->boolean('result_released')->default(false);

            $table->timestamps();
            $table->unique(['exam_id', 'user_id']);
        });

        // Submissions: hubungkan ke exam_attempt agar 1 jawaban milik 1 attempt
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('exam_attempt_id')->nullable()->after('user_id')
                  ->constrained('exam_attempts')->nullOnDelete();
            $table->boolean('manually_graded')->default(false)->after('status');
            $table->foreignId('graded_by')->nullable()->after('manually_graded')
                  ->constrained('users')->nullOnDelete();
        });

        // Submission status enum perlu menerima 'submitted'
        // (di MySQL kita pakai change DDL via Doctrine—tapi DB ini pakai enum string,
        //  status existing: pending|graded|failed. Tambah 'submitted' lewat raw alter.)
        if (config('database.default') !== 'sqlite') {
            \DB::statement("ALTER TABLE submissions MODIFY status ENUM('pending','submitted','graded','failed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_attempt_id');
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn('manually_graded');
        });
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exams');
    }
};
