<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan dukungan soal pilihan ganda + posisi soal.
 *
 * - questions.type   : enum diperluas ke 'essay' | 'mcq' (default 'essay')
 * - questions.options: JSON daftar opsi (untuk mcq)  → [{ "key": "A", "text": "..." }, ...]
 * - questions.correct_option: kunci jawaban benar (A/B/C/D/...)
 * - questions.position: urutan tampilan (untuk soal campuran)
 * - submissions.selected_option: jawaban MCQ siswa
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Perluas enum type pada `questions`
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE questions MODIFY type ENUM('essay','mcq') NOT NULL DEFAULT 'essay'");
        }

        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'options')) {
                $table->json('options')->nullable()->after('rubric');
            }
            if (! Schema::hasColumn('questions', 'correct_option')) {
                $table->string('correct_option', 8)->nullable()->after('options');
            }
            if (! Schema::hasColumn('questions', 'position')) {
                $table->unsignedSmallInteger('position')->default(0)->after('correct_option');
            }
        });

        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'selected_option')) {
                $table->string('selected_option', 8)->nullable()->after('answer_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (Schema::hasColumn('submissions', 'selected_option')) {
                $table->dropColumn('selected_option');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            foreach (['options', 'correct_option', 'position'] as $col) {
                if (Schema::hasColumn('questions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE questions MODIFY type ENUM('essay') NOT NULL DEFAULT 'essay'");
        }
    }
};
